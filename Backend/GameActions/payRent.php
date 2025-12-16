<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$gameId     = (int)($data['gameId'] ?? 0);
$payerId    = (int)($data['payerId'] ?? 0);
$propertyId = (int)($data['propertyId'] ?? 0);
$tileIndex  = isset($data['tileIndex']) ? (int)$data['tileIndex'] : null;

if ($gameId <= 0 || $payerId <= 0 || $propertyId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Missing/invalid inputs']);
  exit;
}

try {
  $db->begin_transaction();

  // Lock property, get owner + rent
  $propStmt = $db->prepare("
    SELECT property_id, rent, owner_id, is_mortgaged
    FROM Property
    WHERE property_id = ? AND current_game_id = ?
    FOR UPDATE
  ");
  $propStmt->bind_param("ii", $propertyId, $gameId);
  $propStmt->execute();
  $prop = $propStmt->get_result()->fetch_assoc();
  $propStmt->close();

  if (!$prop) throw new Exception("Property not found for this game");
  if (empty($prop['owner_id'])) throw new Exception("Property is unowned");
  if ((int)$prop['owner_id'] === $payerId) throw new Exception("Owner cannot pay rent to self");
  if ((int)$prop['is_mortgaged'] === 1) throw new Exception("Property is mortgaged; no rent due");

  $receiverId = (int)$prop['owner_id'];
  $amount = (int)$prop['rent'];
  if ($amount <= 0) throw new Exception("Invalid rent amount");

  // Lock payer
  $payerStmt = $db->prepare("
    SELECT money FROM Player
    WHERE player_id = ? AND current_game_id = ?
    FOR UPDATE
  ");
  $payerStmt->bind_param("ii", $payerId, $gameId);
  $payerStmt->execute();
  $payer = $payerStmt->get_result()->fetch_assoc();
  $payerStmt->close();
  if (!$payer) throw new Exception("Payer not found in this game");
  if ((int)$payer['money'] < $amount) throw new Exception("Insufficient funds to pay rent");

  // Lock receiver
  $recvStmt = $db->prepare("
    SELECT money FROM Player
    WHERE player_id = ? AND current_game_id = ?
    FOR UPDATE
  ");
  $recvStmt->bind_param("ii", $receiverId, $gameId);
  $recvStmt->execute();
  $recv = $recvStmt->get_result()->fetch_assoc();
  $recvStmt->close();
  if (!$recv) throw new Exception("Owner not found in this game");

  // Update balances
  $u1 = $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ?");
  $u1->bind_param("ii", $amount, $payerId);
  $u1->execute();
  $u1->close();

  $u2 = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?");
  $u2->bind_param("ii", $amount, $receiverId);
  $u2->execute();
  $u2->close();

  // PersonalTransaction
  $pt = $db->prepare("
    INSERT INTO PersonalTransaction (from_player_id, to_player_id, amount, timestamp)
    VALUES (?, ?, ?, NOW())
  ");
  $pt->bind_param("iii", $payerId, $receiverId, $amount);
  $pt->execute();
  $pt->close();

  // Log
  $desc = "Rent paid: Player {$payerId} -> Player {$receiverId}, amount {$amount}, property {$propertyId}";
  if ($tileIndex !== null) $desc .= ", tile {$tileIndex}";
  $l = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
  $l->bind_param("is", $gameId, $desc);
  $l->execute();
  $l->close();

  // Return balances
  $b1 = $db->prepare("SELECT money FROM Player WHERE player_id = ?");
  $b1->bind_param("i", $payerId);
  $b1->execute();
  $payerNew = $b1->get_result()->fetch_assoc();
  $b1->close();

  $b2 = $db->prepare("SELECT money FROM Player WHERE player_id = ?");
  $b2->bind_param("i", $receiverId);
  $b2->execute();
  $recvNew = $b2->get_result()->fetch_assoc();
  $b2->close();

  $db->commit();

  echo json_encode([
    'success' => true,
    'amount' => $amount,
    'receiverId' => $receiverId,
    'payerNewBalance' => (int)($payerNew['money'] ?? 0),
    'receiverNewBalance' => (int)($recvNew['money'] ?? 0),
  ]);
} catch (Exception $e) {
  $db->rollback();
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
