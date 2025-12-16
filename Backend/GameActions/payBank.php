<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$gameId   = (int)($data['gameId'] ?? 0);
$playerId = (int)($data['playerId'] ?? 0);
$type     = trim((string)($data['type'] ?? '')); // "tax" | "card" | etc.
$tileIndex = isset($data['tileIndex']) ? (int)$data['tileIndex'] : null;

$amount   = isset($data['amount']) ? (int)$data['amount'] : 0;
$reason   = trim((string)($data['reason'] ?? ''));

if ($gameId <= 0 || $playerId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Missing/invalid gameId/playerId']);
  exit;
}

try {
  // AUTO CALC
  if ($type === "tax") {
    if ($tileIndex === null) throw new Exception("tileIndex required for tax");
    // your chosen values:
    if ($tileIndex === 4) $amount = 200;      // INCOME TAX
    else if ($tileIndex === 38) $amount = 100; // LUXURY TAX
    else $amount = 100; // fallback
    if ($reason === '') $reason = "Tax tile {$tileIndex}";
  } else {
    // card/manual must include amount
    if ($amount <= 0) throw new Exception("amount required");
    if ($reason === '') $reason = $type !== '' ? $type : "payment";
  }

  $db->begin_transaction();

  // lock player
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
  if ((int)$p['money'] < $amount) throw new Exception("Insufficient funds");

  // lock bank
  $bStmt = $db->prepare("SELECT bank_id FROM Bank WHERE game_id = ? FOR UPDATE");
  $bStmt->bind_param("i", $gameId);
  $bStmt->execute();
  $bank = $bStmt->get_result()->fetch_assoc();
  $bStmt->close();
  if (!$bank) throw new Exception("Bank not found for this game");
  $bankId = (int)$bank['bank_id'];

  // update balances
  $u1 = $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ?");
  $u1->bind_param("ii", $amount, $playerId);
  $u1->execute();
  $u1->close();

  $u2 = $db->prepare("UPDATE Bank SET total_funds = total_funds + ? WHERE bank_id = ?");
  $u2->bind_param("ii", $amount, $bankId);
  $u2->execute();
  $u2->close();

  // BankTransaction enum limited -> use 'loan' as generic
  $tType = "loan";
  $nullProp = null;
  $bt = $db->prepare("
    INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
    VALUES (?, ?, ?, ?, ?, NOW())
  ");
  $bt->bind_param("iiisi", $bankId, $playerId, $nullProp, $tType, $amount);
  $bt->execute();
  $bt->close();

  // log
  $desc = "Player {$playerId} paid bank {$amount}";
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
    'newBalance' => (int)($row['money'] ?? 0),
  ]);
} catch (Exception $e) {
  $db->rollback();
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
