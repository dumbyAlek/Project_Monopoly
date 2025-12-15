<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['buyer_id'], $data['owner_id'], $data['property_id'], $data['offer'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}


$buyer_id = (int)$data['buyer_id'];
$owner_id = (int)$data['owner_id'];
$property_id = (int)$data['property_id'];
$offer = (int)$data['offer'];

try {
    $db->begin_transaction();

    // Lock players
    $buyer = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ? FOR UPDATE");
    $buyer->bind_param("i", $buyer_id);
    $buyer->execute();
    $buyerRow = $buyer->get_result()->fetch_assoc();
    $buyer->close();

    if (!$buyerRow) throw new Exception("Buyer not found.");
    $buyerMoney = (int)$buyerRow['money'];
    $gameId = (int)$buyerRow['current_game_id'];

    if ($buyerMoney < $offer) {
        throw new Exception("Buyer does not have enough money.");
    }

    // Verify ownership
    $prop = $db->prepare(
        "SELECT owner_id, current_game_id, price FROM Property WHERE property_id = ? FOR UPDATE"
    );
    $prop->bind_param("i", $property_id);
    $prop->execute();
    $property = $prop->get_result()->fetch_assoc();
    $prop->close();

    if (!$property) throw new Exception("Property not found.");
    if ((int)$property['current_game_id'] !== $gameId) throw new Exception("Property not in this game.");

    if ((int)$property['owner_id'] !== (int)$owner_id) {
        throw new Exception("Property ownership mismatch.");
    }

    // $st = $db->prepare("SELECT money FROM Player WHERE player_id = ? FOR UPDATE");
    // $st->bind_param("i", $owner_id);
    // $st->execute();
    // $st->close();

    // Money transfer
    $st = $db->prepare("UPDATE Player SET money = money - ? WHERE player_id = ?");
    $st->bind_param("ii", $offer, $buyer_id);
    $st->execute();
    $st->close();

    $st = $db->prepare("UPDATE Player SET money = money + ? WHERE player_id = ?");
    $st->bind_param("ii", $offer, $owner_id);
    $st->execute();
    $st->close();

    // Property transfer
    $st = $db->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ? AND current_game_id = ?");
    $st->bind_param("iii", $buyer_id, $property_id, $gameId);
    $st->execute();
    if ($st->affected_rows !== 1) throw new Exception("Failed to transfer property.");
    $st->close();

    $price = (int)$property['price'];

    // Wallet update
    $st = $db->prepare("
        UPDATE Wallet
        SET number_of_properties = number_of_properties + 1,
            propertyWorthCash = propertyWorthCash + ?
        WHERE player_id = ?
    ");
    $st->bind_param("ii", $price, $buyer_id);
    $st->execute();
    $st->close();

    $st = $db->prepare("
        UPDATE Wallet
        SET number_of_properties = number_of_properties - 1,
            propertyWorthCash = propertyWorthCash - ?
        WHERE player_id = ?
    ");
    $st->bind_param("ii", $price, $owner_id);
    $st->execute();
    $st->close();

    // Personal transaction
    $st = $db->prepare("
        INSERT INTO PersonalTransaction (from_player_id, to_player_id, amount, timestamp)
        VALUES (?, ?, ?, NOW())
    ");
    $st->bind_param("iii", $buyer_id, $owner_id, $offer);
    $st->execute();
    $st->close();

    // Log
    $desc = "Player $buyer_id bought property $property_id from Player $owner_id for $$offer";
    $st = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $st->bind_param("is", $gameId, $desc);
    $st->execute();
    $st->close();

    $newBalance = $buyerMoney - $offer;


    $db->commit();

    echo json_encode(["success" => true, "newBalance" => $newBalance, "owned" => true]);
    exit;


} catch (Exception $e) {
    $db->rollback();
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
    exit;
}
