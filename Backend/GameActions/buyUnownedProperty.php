<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$playerId = intval($data['playerId']);
$propertyId = intval($data['propertyId']);

$db = Database::getInstance()->getConnection();

try {
    // Get player info
    $playerStmt = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ?");
    $playerStmt->execute([$playerId]);
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$player) throw new Exception("Player not found");

    $gameId = $player['current_game_id'];

    // Get property info
    $propStmt = $db->prepare("SELECT price, owner_id FROM Property WHERE property_id = ? AND current_game_id = ?");
    $propStmt->execute([$propertyId, $gameId]);
    $property = $propStmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) throw new Exception("Property not found");

    if ($property['owner_id'] !== null) {
        echo json_encode(['success' => false, 'message' => 'Property already owned']);
        exit;
    }

    $price = $property['price'];
    if ($player['money'] < $price) {
        echo json_encode(['success' => false, 'message' => 'Insufficient funds']);
        exit;
    }

    // Get bank for this game
    $bankStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ?");
    $bankStmt->execute([$gameId]);
    $bank = $bankStmt->fetch(PDO::FETCH_ASSOC);
    if (!$bank) throw new Exception("Bank not found");

    $db->beginTransaction();

    // Deduct money from player
    $updatePlayer = $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ?");
    $updatePlayer->execute([$price, $playerId]);

    // Add money to bank
    $updateBank = $db->prepare("UPDATE Bank SET total_funds = total_funds + ? WHERE bank_id = ?");
    $updateBank->execute([$price, $bank['bank_id']]);

    // Set property owner
    $updateProp = $db->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ?");
    $updateProp->execute([$playerId, $propertyId]);

    // Update wallet
    $walletStmt = $db->prepare("INSERT INTO Wallet (player_id, propertyWorthCash, number_of_properties) 
                                VALUES (?, ?, 1) 
                                ON DUPLICATE KEY UPDATE 
                                propertyWorthCash = propertyWorthCash + ?, 
                                number_of_properties = number_of_properties + 1");
    $walletStmt->execute([$playerId, $price, $price]);

    // Log bank transaction
    $bankTransStmt = $db->prepare("INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
                                   VALUES (?, ?, ?, 'purchase', ?, NOW())");
    $bankTransStmt->execute([$bank['bank_id'], $playerId, $propertyId, $price]);

    // Log action
    $logStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $logStmt->execute([$gameId, "Player $playerId bought property $propertyId from bank"]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'newBalance' => $player['money'] - $price,
        'owned' => true,
        'message' => 'Property purchased from bank'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
