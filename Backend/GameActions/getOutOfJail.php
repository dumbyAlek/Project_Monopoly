<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// read JSON or POST
$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) $input = $_POST;

$playerId = isset($input['playerId']) ? (int)$input['playerId'] : 0;
$useCard  = isset($input['useCard']) ? (bool)$input['useCard'] : false;
$gameId   = isset($input['gameId']) ? (int)$input['gameId'] : 0;

if ($playerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'playerId missing']);
    exit;
}

try {
    $db->begin_transaction();

    // Lock player row
    $pStmt = $db->prepare("
        SELECT player_id, money, is_in_jail, has_get_out_card, current_game_id
        FROM Player
        WHERE player_id = ?
        FOR UPDATE
    ");
    $pStmt->bind_param("i", $playerId);
    $pStmt->execute();
    $player = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    if (!$player) throw new Exception("Player not found");
    if ((int)$player['is_in_jail'] !== 1) throw new Exception("Player is not in jail");

    $effectiveGameId = $gameId > 0 ? $gameId : (int)$player['current_game_id'];
    if ($effectiveGameId <= 0) throw new Exception("Game not found for player");

    // Fine amount from Game.passing_GO or default
    $gStmt = $db->prepare("SELECT passing_GO FROM Game WHERE game_id = ?");
    $gStmt->bind_param("i", $effectiveGameId);
    $gStmt->execute();
    $game = $gStmt->get_result()->fetch_assoc();
    $gStmt->close();

    $fine = $game ? (int)$game['passing_GO'] : 50;
    if ($fine <= 0) $fine = 50;

    // CASE 1: Use Get Out of Jail card
    if ($useCard) {
        if ((int)$player['has_get_out_card'] !== 1) {
            throw new Exception("No Get Out of Jail card available");
        }

        $uStmt = $db->prepare("
            UPDATE Player
            SET is_in_jail = 0, has_get_out_card = 0
            WHERE player_id = ?
        ");
        $uStmt->bind_param("i", $playerId);
        $uStmt->execute();
        $uStmt->close();

        $logTxt = "Player {$playerId} used Get Out of Jail card.";
        $lStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
        $lStmt->bind_param("is", $effectiveGameId, $logTxt);
        $lStmt->execute();
        $lStmt->close();

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Released using card', 'fine_paid' => 0, 'newBalance' => (int)$player['money']]);
        exit;
    }

    // CASE 2: Pay fine
    if ((int)$player['money'] < $fine) {
        throw new Exception("Not enough money to pay fine");
    }

    // Lock bank row
    $bStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ? FOR UPDATE");
    $bStmt->bind_param("i", $effectiveGameId);
    $bStmt->execute();
    $bank = $bStmt->get_result()->fetch_assoc();
    $bStmt->close();

    if (!$bank) throw new Exception("Bank not found for game");

    // Deduct money from player & add to bank
    $uP = $db->prepare("UPDATE Player SET is_in_jail = 0, money = money - ? WHERE player_id = ?");
    $uP->bind_param("ii", $fine, $playerId);
    $uP->execute();
    $uP->close();

    $uB = $db->prepare("UPDATE Bank SET total_funds = total_funds + ? WHERE bank_id = ?");
    $uB->bind_param("ii", $fine, $bank['bank_id']);
    $uB->execute();
    $uB->close();

    // Record bank transaction
    $tType = "jail_fine";
    $nullProp = null;
    $bt = $db->prepare("
        INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $bt->bind_param("iiisi", $bank['bank_id'], $playerId, $nullProp, $tType, $fine);
    $bt->execute();
    $bt->close();

    // Log
    $logTxt = "Player {$playerId} paid jail fine {$fine} to bank.";
    $lStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $lStmt->bind_param("is", $effectiveGameId, $logTxt);
    $lStmt->execute();
    $lStmt->close();

    // Get new balance
    $mStmt = $db->prepare("SELECT money FROM Player WHERE player_id = ?");
    $mStmt->bind_param("i", $playerId);
    $mStmt->execute();
    $newMoney = $mStmt->get_result()->fetch_assoc();
    $mStmt->close();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Released after paying fine',
        'fine_paid' => $fine,
        'newBalance' => (int)($newMoney['money'] ?? 0)
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
