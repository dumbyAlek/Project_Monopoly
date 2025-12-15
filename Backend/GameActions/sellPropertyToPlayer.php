<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

$owner_id   = (int)$data['owner_id'];
$buyer_id   = (int)$data['buyer_id'];
$property_id = (int)$data['property_id'];
$price      = (int)$data['price'];
if ($buyer_id === $owner_id) {
    throw new Exception("Buyer cannot be the seller.");
}


try {
    $db->begin_transaction();

    $gameIdClient = (int)($data['gameId'] ?? 0);

    // Lock buyer
    $buyerStmt = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ? FOR UPDATE");
    $buyerStmt->bind_param("i", $buyer_id);
    $buyerStmt->execute();
    $buyerRow = $buyerStmt->get_result()->fetch_assoc();
    $buyerStmt->close();

    if (!$buyerRow) throw new Exception("Buyer not found.");
    $buyerMoney = (int)$buyerRow['money'];
    $gameId = (int)$buyerRow['current_game_id'];

    if ($gameIdClient && $gameIdClient !== $gameId) {
        throw new Exception("Game mismatch.");
    }

    // Lock seller
    $sellerStmt = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ? FOR UPDATE");
    $sellerStmt->bind_param("i", $owner_id);
    $sellerStmt->execute();
    $sellerRow = $sellerStmt->get_result()->fetch_assoc();
    $sellerStmt->close();

    if (!$sellerRow) throw new Exception("Seller not found.");
    if ((int)$sellerRow['current_game_id'] !== $gameId) {
        throw new Exception("Players are not in the same game.");
    }

    if ($buyerMoney < $price) {
        throw new Exception("Buyer does not have enough money.");
    }

    // Lock property
    $propStmt = $db->prepare("
        SELECT owner_id, price
        FROM Property
        WHERE property_id = ? AND current_game_id = ?
        FOR UPDATE
    ");
    $propStmt->bind_param("ii", $property_id, $gameId);
    $propStmt->execute();
    $property = $propStmt->get_result()->fetch_assoc();
    $propStmt->close();

    if (!$property) throw new Exception("Property not found in this game.");
    if ((int)$property['owner_id'] !== (int)$owner_id) throw new Exception("Seller does not own this property.");

    // Money transfer
    $st = $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ? AND current_game_id = ?");
    $st->bind_param("iii", $price, $buyer_id, $gameId);
    $st->execute();
    $st->close();

    $st = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ? AND current_game_id = ?");
    $st->bind_param("iii", $price, $owner_id, $gameId);
    $st->execute();
    $st->close();

    // Property ownership transfer
    $st = $db->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ? AND current_game_id = ?");
    $st->bind_param("iii", $buyer_id, $property_id, $gameId);
    $st->execute();
    if ($st->affected_rows !== 1) throw new Exception("Failed to transfer property.");
    $st->close();

    $propPrice = (int)$property['price'];

    // Wallet updates
    $st = $db->prepare("
        UPDATE Wallet
        SET number_of_properties = number_of_properties - 1,
            propertyWorthCash = propertyWorthCash - ?
        WHERE player_id = ?
    ");
    $st->bind_param("ii", $propPrice, $owner_id);
    $st->execute();
    $st->close();

    $st = $db->prepare("
        UPDATE Wallet
        SET number_of_properties = number_of_properties + 1,
            propertyWorthCash = propertyWorthCash + ?
        WHERE player_id = ?
    ");
    $st->bind_param("ii", $propPrice, $buyer_id);
    $st->execute();
    $st->close();

    // Personal transaction log
    $st = $db->prepare("
        INSERT INTO PersonalTransaction (from_player_id, to_player_id, amount, timestamp)
        VALUES (?, ?, ?, NOW())
    ");
    $st->bind_param("iii", $buyer_id, $owner_id, $price);
    $st->execute();
    $st->close();

    // Game log
    $desc = "Player $owner_id sold property $property_id to Player $buyer_id for $$price";
    $logStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $logStmt->bind_param("is", $gameId, $desc);
    $logStmt->execute();
    $logStmt->close();


    $db->commit();

    $buyerNewBalance  = $buyerMoney - $price;
    $sellerNewBalance = ((int)$sellerRow['money']) + $price;

    echo json_encode([
    "success" => true,
    "message" => "Property sold successfully.",
    "buyerNewBalance" => $buyerNewBalance,
    "sellerNewBalance" => $sellerNewBalance,
    "newOwnerId" => $buyer_id
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
exit;