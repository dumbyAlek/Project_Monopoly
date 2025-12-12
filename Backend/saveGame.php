<?php
session_start();
require_once __DIR__ . '/../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['gameId'], $data['bank'], $data['players'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$db = Database::getInstance()->getConnection();
$gameId = $data['gameId'];
$bank = $data['bank'];
$players = $data['players'];

try {
    $db->begin_transaction();

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