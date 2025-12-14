<?php
require_once "../../Database/Database.php";

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

$owner_id   = (int)$data['owner_id'];
$buyer_id   = (int)$data['buyer_id'];
$property_id = (int)$data['property_id'];
$price      = (int)$data['price'];

try {
    $db->beginTransaction();

    // Lock buyer
    $buyerStmt = $db->prepare(
        "SELECT money FROM Player WHERE player_id = ? FOR UPDATE"
    );
    $buyerStmt->execute([$buyer_id]);
    $buyerMoney = $buyerStmt->fetchColumn();

    if ($buyerMoney < $price) {
        throw new Exception("Buyer does not have enough money.");
    }

    // Lock property
    $propStmt = $db->prepare(
        "SELECT owner_id, current_game_id 
         FROM Property 
         WHERE property_id = ? FOR UPDATE"
    );
    $propStmt->execute([$property_id]);
    $property = $propStmt->fetch(PDO::FETCH_ASSOC);

    if ($property['owner_id'] != $owner_id) {
        throw new Exception("Seller does not own this property.");
    }

    // Money transfer
    $db->prepare(
        "UPDATE Player SET money = money - ? WHERE player_id = ?"
    )->execute([$price, $buyer_id]);

    $db->prepare(
        "UPDATE Player SET money = money + ? WHERE player_id = ?"
    )->execute([$price, $owner_id]);

    // Property ownership transfer
    $db->prepare(
        "UPDATE Property SET owner_id = ? WHERE property_id = ?"
    )->execute([$buyer_id, $property_id]);

    // Wallet updates
    $db->prepare(
        "UPDATE Wallet SET number_of_properties = number_of_properties - 1
         WHERE player_id = ?"
    )->execute([$owner_id]);

    $db->prepare(
        "UPDATE Wallet SET number_of_properties = number_of_properties + 1
         WHERE player_id = ?"
    )->execute([$buyer_id]);

    // Personal transaction log
    $db->prepare(
        "INSERT INTO PersonalTransaction
        (from_player_id, to_player_id, amount, timestamp)
        VALUES (?, ?, ?, NOW())"
    )->execute([$buyer_id, $owner_id, $price]);

    // Game log
    $db->prepare(
        "INSERT INTO Log (game_id, description, timestamp)
        VALUES (?, ?, NOW())"
    )->execute([
        $property['current_game_id'],
        "Player $owner_id sold property $property_id to Player $buyer_id for $$price"
    ]);

    $db->commit();

    echo json_encode(["success" => true]);

    echo json_encode([
        "success" => true,
        "newBalance" => $updatedMoney,
        "numProperties" => $updatedPropCount
    ]);


} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
