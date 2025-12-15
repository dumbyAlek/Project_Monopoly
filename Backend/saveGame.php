<?php
session_start();
require_once __DIR__ . '/../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['gameId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input: missing gameId']);
    exit;
}

// Allow 2 modes: light autosave: only gameId and full save: gameId + bank + players
$isFullSave = isset($data['bank'], $data['players']);


$db = Database::getInstance()->getConnection();
$gameId = (int)$data['gameId'];
$bank = $data['bank'] ?? null;
$players = $data['players'] ?? null;

try {
    $db->begin_transaction();

    // LIGHT AUTOSAVE: only update last_saved_time
    if (!$isFullSave) {
        $st = $db->prepare("UPDATE Game SET last_saved_time = NOW() WHERE game_id = ?");
        $st->bind_param("i", $gameId);
        $st->execute();
        $db->commit();
        echo json_encode(['success' => true, 'mode' => 'light']);
        exit;
    }


    // Update Bank
    $stmt = $db->prepare("UPDATE Bank SET total_funds = ? WHERE game_id = ?");
    $stmt->bind_param("ii", $bank['totalFunds'], $gameId);
    $stmt->execute();


    // Update each player and wallet
    foreach ($players as $p) {

        // FIX: you must assign values first (can't bind expressions)
        $money = (int)$p['money'];
        $inJail = $p['inJail'] ? 1 : 0;
        $hasCard = $p['hasGetOutCard'] ? 1 : 0;
        $playerId = (int)$p['playerId'];

        // Update Player
        $stmtPlayer = $db->prepare("
            UPDATE Player 
            SET money = ?, is_in_jail = ?, has_get_out_card = ? 
            WHERE player_id = ?
        ");
        $stmtPlayer->bind_param("iiii", $money, $inJail, $hasCard, $playerId);
        $stmtPlayer->execute();


        // Wallet values
        $propCount = (int)$p['propertiesCount'];
        $propWorth = (int)$p['propertiesWorth'];
        $debtTo = (int)$p['debtToPlayers'];
        $debtFrom = (int)$p['debtFromPlayers'];

        // Update Wallet
        $stmtWallet = $db->prepare("
            UPDATE Wallet 
            SET number_of_properties = ?, propertyWorthCash = ?, debt_to_players = ?, debt_from_players = ?
            WHERE player_id = ?
        ");
        $stmtWallet->bind_param("iiiii", $propCount, $propWorth, $debtTo, $debtFrom, $playerId);
        $stmtWallet->execute();
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>