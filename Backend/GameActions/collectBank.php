<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$gameId   = (int)($data['gameId'] ?? 0);
$playerId = (int)($data['playerId'] ?? 0);
$amount   = (int)($data['amount'] ?? 0);
$reason   = trim((string)($data['reason'] ?? ''));

if ($gameId <= 0 || $playerId <= 0 || $amount <= 0) {
  echo json_encode(['success' => false, 'message' => 'Missing/invalid inputs']);
  exit;
}

try {
  $db->begin_transaction();

  $bStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ? FOR UPDATE");
  $bStmt->bind_param("i", $gameId);
  $bStmt->execute();
  $bank = $bStmt->get_result()->fetch_assoc();
  $bStmt->close();
  if (!$bank) throw new Exception("Bank not found");

  $bankId = (int)$bank['bank_id'];
  if ((int)$bank['total_funds'] < $amount) throw new Exception("Bank has insufficient funds");

  $pStmt = $db->prepare("
    SELECT money FROM Player
    WHERE player_id = ? AND current_game_id = ?
    FOR UPDATE
  ");
  $pStmt->bind_param("ii", $playerId, $gameId);
  $pStmt->execute();
  $p = $pStmt->get_result()->fetch_assoc();
  $pStmt->close();
  if (!$p) throw new Exception("Player not found in this game");

  $u1 = $db->prepare("UPDATE Bank SET total_funds = total_funds - ? WHERE bank_id = ?");
  $u1->bind_param("ii", $amount, $bankId);
  $u1->execute();
  $u1->close();

  $u2 = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?");
  $u2->bind_param("ii", $amount, $playerId);
  $u2->execute();
  $u2->close();

  // BankTransaction (enum limited) -> 'loan'
  $tType = "loan";
  $nullProp = null;
  $bt = $db->prepare("
    INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");
  $bt->bind_param("iiisi", $bankId, $playerId, $nullProp, $tType, $amount);
  $bt->execute();
  $bt->close();

  $desc = "Bank paid Player {$playerId} {$amount}";
  if ($reason !== '') $desc .= " ({$reason})";
  $l = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
  $l->bind_param("is", $gameId, $desc);
  $l->execute();
  $l->close();

  $m = $db->prepare("SELECT money FROM Player WHERE player_id = ?");
  $m->bind_param("i", $playerId);
  $m->execute();
  $row = $m->get_result()->fetch_assoc();
  $m->close();

  $db->commit();

  echo json_encode([
    'success' => true,
    'amount' => $amount,
    'newBalance' => (int)($row['money'] ?? 0)
  ]);
} catch (Exception $e) {
  $db->rollback();
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
