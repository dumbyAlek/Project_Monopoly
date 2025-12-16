<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['gameId'], $data['playerId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$gameId   = (int)$data['gameId'];
$playerId = (int)$data['playerId'];

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    $stmt = $db->prepare("SELECT passing_GO FROM Game WHERE game_id = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
    if (!$game) throw new Exception("Game not found");

    $amount = (int)$game['passing_GO'];

    $stmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $bank = $stmt->get_result()->fetch_assoc();
    if (!$bank) throw new Exception("Bank not found");

    if ($bank['total_funds'] < $amount) {
        throw new Exception("Bank has insufficient funds");
    }

    $stmt = $db->prepare(
        "UPDATE Bank SET total_funds = total_funds - ? WHERE bank_id = ?"
    );
    $stmt->bind_param("ii", $amount, $bank['bank_id']);
    $stmt->execute();

    $stmt = $db->prepare(
        "UPDATE Player 
        SET money = money + ? 
        WHERE player_id = ? AND current_game_id = ?"
    );
    $stmt->bind_param("iii", $amount, $playerId, $gameId);

    $stmt->execute();

    $stmt = $db->prepare(
        "INSERT INTO BankTransaction
         (bank_id, player_id, property_id, type, amount, timestamp)
         VALUES (?, ?, NULL, 'loan', ?, NOW())"
    );
    $stmt->bind_param("iii", $bank['bank_id'], $playerId, $amount);
    $stmt->execute();

    $stmt = $db->prepare(
        "INSERT INTO Log (game_id, description, timestamp)
         VALUES (?, ?, NOW())"
    );
    $desc = "Player {$playerId} passed GO and received {$amount}";
    $stmt->bind_param("is", $gameId, $desc);
    $stmt->execute();

    $stmt = $db->prepare(
        "SELECT money FROM Player WHERE player_id = ? AND current_game_id = ?"
    );
    $stmt->bind_param("ii", $playerId, $gameId);

    $stmt->execute();
    $player = $stmt->get_result()->fetch_assoc();

    $db->commit();

    echo json_encode([
        'success'    => true,
        'amount'     => $amount,
        'newBalance' => (int)$player['money']
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}