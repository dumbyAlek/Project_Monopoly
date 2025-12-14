<?php
require_once "../../Database/Database.php";

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

$buyer_id = (int)$data['buyer_id'];
$owner_id = (int)$data['owner_id'];
$property_id = (int)$data['property_id'];
$offer = (int)$data['offer'];

try {
    $db->beginTransaction();

    // Lock players
    $buyer = $db->prepare("SELECT money FROM Player WHERE player_id = ? FOR UPDATE");
    $buyer->execute([$buyer_id]);
    $buyerMoney = $buyer->fetchColumn();

    if ($buyerMoney < $offer) {
        throw new Exception("Buyer does not have enough money.");
    }

    // Verify ownership
    $prop = $db->prepare(
        "SELECT owner_id, current_game_id FROM Property WHERE property_id = ? FOR UPDATE"
    );
    $prop->execute([$property_id]);
    $property = $prop->fetch(PDO::FETCH_ASSOC);

    if ($property['owner_id'] != $owner_id) {
        throw new Exception("Property ownership mismatch.");
    }

    // Money transfer
    $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ?")
        ->execute([$offer, $buyer_id]);

    $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?")
        ->execute([$offer, $owner_id]);

    // Property transfer
    $db->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ?")
        ->execute([$buyer_id, $property_id]);

    // Wallet update
    $db->prepare(
        "UPDATE Wallet 
         SET number_of_properties = number_of_properties + 1 
         WHERE player_id = ?"
    )->execute([$buyer_id]);

    $db->prepare(
        "UPDATE Wallet 
         SET number_of_properties = number_of_properties - 1 
         WHERE player_id = ?"
    )->execute([$owner_id]);

    // Personal transaction
    $db->prepare(
        "INSERT INTO PersonalTransaction 
        (from_player_id, to_player_id, amount, timestamp)
        VALUES (?, ?, ?, NOW())"
    )->execute([$buyer_id, $owner_id, $offer]);

    // Log
    $db->prepare(
        "INSERT INTO Log (game_id, description, timestamp)
         VALUES (?, ?, NOW())"
    )->execute([
        $property['current_game_id'],
        "Player $buyer_id bought property $property_id from Player $owner_id for $$offer"
    ]);

    $db->commit();

    echo json_encode(["success" => true]);


} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
