<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$playerId = intval($data['sellerId']);
$propertyId = intval($data['propertyId']);

$db = Database::getInstance()->getConnection();
$db->begin_transaction();

try {
    // Get player info
    $playerStmt = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ? FOR UPDATE");
    $playerStmt->bind_param("i", $playerId);
    $playerStmt->execute();
    $player = $playerStmt->get_result()->fetch_assoc();
    $playerStmt->close();

    if (!$player) throw new Exception("Player not found");

    $gameId = $player['current_game_id'];

    // Get property info
    $propStmt = $db->prepare("
    SELECT price, house_count, hotel_count, owner_id
    FROM Property
    WHERE property_id = ? AND current_game_id = ?
    FOR UPDATE
    ");
    $propStmt->bind_param("ii", $propertyId, $gameId);
    $propStmt->execute();
    $property = $propStmt->get_result()->fetch_assoc();
    $propStmt->close();

    if (!$property) throw new Exception("Property not found");

    if ((int)$property['owner_id'] !== (int)$playerId) {
        throw new Exception("You do not own this property");
    }

    // Calculate sell price
    $basePrice  = (int)$property['price'];
    $houseCount = (int)$property['house_count'];
    $hotelCount = (int)$property['hotel_count'];
    $sellPrice = $basePrice + ($houseCount * 50) + ($hotelCount * 50);

    // Get bank for this game
    $bankStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ? FOR UPDATE");
    $bankStmt->bind_param("i", $gameId);
    $bankStmt->execute();
    $bank = $bankStmt->get_result()->fetch_assoc();
    $bankStmt->close();

    if (!$bank) throw new Exception("Bank not found");
    if ((int)$bank['total_funds'] < (int)$sellPrice) {
        throw new Exception("Bank does not have enough funds to buy this property.");
    }



    // Add money to player
    $updatePlayer = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?");
    $updatePlayer->bind_param("ii", $sellPrice, $playerId);
    $updatePlayer->execute();
    if ($updatePlayer->affected_rows !== 1) throw new Exception("Failed to update player money.");

    // Deduct money from bank
    $bankId = (int)$bank['bank_id'];
    $updateBank = $db->prepare("UPDATE Bank SET total_funds = total_funds - ? WHERE bank_id = ?");
    $updateBank->bind_param("ii", $sellPrice, $bankId);
    $updateBank->execute();
    if ($updateBank->affected_rows !== 1) throw new Exception("Failed to update bank money.");

    // Remove ownership
    $updateProp = $db->prepare("
    UPDATE Property
    SET owner_id = NULL, house_count = 0, hotel_count = 0
    WHERE property_id = ? AND current_game_id = ?
    ");
    $updateProp->bind_param("ii", $propertyId, $gameId);
    $updateProp->execute();
    if ($updateProp->affected_rows !== 1) {
        throw new Exception("Failed to clear property owner (wrong game_id or already updated).");
    }

    // Update wallet
    $propPrice = (int)$property['price'];
    $walletStmt = $db->prepare("
    UPDATE Wallet
    SET propertyWorthCash = propertyWorthCash - ?, number_of_properties = number_of_properties - 1
    WHERE player_id = ?
    ");
    $walletStmt->bind_param("ii", $propPrice, $playerId);
    $walletStmt->execute();
    if ($walletStmt->affected_rows !== 1) {
        throw new Exception("Wallet update failed (wallet row missing?).");
    }

    // Log bank transaction
    $bankTransStmt = $db->prepare("
    INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
    VALUES (?, ?, ?, 'sell', ?, NOW())
    ");
    $bankTransStmt->bind_param("iiii", $bankId, $playerId, $propertyId, $sellPrice);
    $bankTransStmt->execute();

    // Log action
    $desc = "Player $playerId sold property $propertyId to bank";
    $logStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $logStmt->bind_param("is", $gameId, $desc);
    $logStmt->execute();

    $db->commit();
    $oldMoney = (int)$player['money'];
    $newMoney = $oldMoney + (int)$sellPrice;
    $updatePlayer->close();
    $updateBank->close();
    $updateProp->close();
    $walletStmt->close();
    $bankTransStmt->close();
    $logStmt->close();

    echo json_encode([
        'success' => true,
        'newBalance' => $newMoney,
        'owned' => false,
        'message' => 'Property sold to bank'
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
