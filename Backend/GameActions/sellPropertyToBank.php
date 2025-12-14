<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$playerId = intval($data['sellerId']);
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
    $propStmt = $db->prepare("SELECT price, house_count, hotel_count, owner_id FROM Property WHERE property_id = ? AND current_game_id = ?");
    $propStmt->execute([$propertyId, $gameId]);
    $property = $propStmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) throw new Exception("Property not found");

    if ($property['owner_id'] != $playerId) {
        echo json_encode(['success' => false, 'message' => 'You do not own this property']);
        exit;
    }

    // Calculate sell price
    $sellPrice = $property['price'];
    // Placeholder: houses and hotel cost
    $sellPrice += ($property['house_count'] * 0); // TODO: add house cost
    $sellPrice += ($property['hotel_count'] * 0); // TODO: add hotel cost

    // Get bank for this game
    $bankStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ?");
    $bankStmt->execute([$gameId]);
    $bank = $bankStmt->fetch(PDO::FETCH_ASSOC);
    if (!$bank) throw new Exception("Bank not found");

    $db->beginTransaction();

    // Add money to player
    $updatePlayer = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?");
    $updatePlayer->execute([$sellPrice, $playerId]);

    // Deduct money from bank
    $updateBank = $db->prepare("UPDATE Bank SET total_funds = total_funds - ? WHERE bank_id = ?");
    $updateBank->execute([$sellPrice, $bank['bank_id']]);

    // Remove ownership
    $updateProp = $db->prepare("UPDATE Property SET owner_id = NULL, house_count = 0, hotel_count = 0 WHERE property_id = ?");
    $updateProp->execute([$propertyId]);

    // Update wallet
    $walletStmt = $db->prepare("UPDATE Wallet SET propertyWorthCash = propertyWorthCash - ?, number_of_properties = number_of_properties - 1 WHERE player_id = ?");
    $walletStmt->execute([$property['price'], $playerId]);

    // Log bank transaction
    $bankTransStmt = $db->prepare("INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
                                   VALUES (?, ?, ?, 'sell', ?, NOW())");
    $bankTransStmt->execute([$bank['bank_id'], $playerId, $propertyId, $sellPrice]);

    // Log action
    $logStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $logStmt->execute([$gameId, "Player $playerId sold property $propertyId to bank"]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'newBalance' => $player['money'] + $sellPrice,
        'owned' => false,
        'message' => 'Property sold to bank'
    ]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
