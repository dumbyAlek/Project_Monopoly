<?php
die("LOADED FILE: " . __FILE__);
session_start();
require_once __DIR__ . '/../../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$gameId     = (int)$data['gameId'];
$playerId   = (int)$data['playerId'];
$propertyId = (int)$data['propertyId'];
$tileIndex  = (int)$data['tileIndex'];

$db = Database::getInstance()->getConnection();

try {
    $db->begin_transaction();

    if ($tileIndex < 10) $cost = 50;
    elseif ($tileIndex < 20) $cost = 100;
    elseif ($tileIndex < 30) $cost = 150;
    else $cost = 200;

    $stmt = $db->prepare("
        SELECT owner_id, house_count, hotel_count
        FROM Property
        WHERE property_id = ? AND current_game_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $propertyId, $gameId);
    $stmt->execute();
    $property = $stmt->get_result()->fetch_assoc();

    if (!$property) {
        throw new Exception("Property not found");
    }

    if ((int)$property['owner_id'] !== $playerId) {
        throw new Exception("Player does not own this property");
    }

    $stmt = $db->prepare("
        SELECT money
        FROM Player
        WHERE player_id = ?
        FOR UPDATE
    ");
    $stmt->bind_param("i", $playerId);
    $stmt->execute();
    $player = $stmt->get_result()->fetch_assoc();

    if (!$player || (int)$player['money'] < $cost) {
        throw new Exception("Insufficient Fund");
    }

    $houseCount = (int)$property['house_count'];
    $hotelCount = (int)$property['hotel_count'];
    $message    = "";

    if ($hotelCount > 0) {
        throw new Exception("Max Number of Houses/Hotel Placed");
    }

    if ($houseCount >= 0 && $houseCount < 4) {
        $houseCount++;
        $message = "House {$houseCount} placed successfully";
    } elseif ($houseCount === 4) {
        $houseCount = 0;
        $hotelCount = 1;
        $message = "Hotel placed successfully";
    }

    $stmt = $db->prepare("
        UPDATE Property
        SET house_count = ?, hotel_count = ?
        WHERE property_id = ?
    ");
    $stmt->bind_param("iii", $houseCount, $hotelCount, $propertyId);
    $stmt->execute();

    $stmt = $db->prepare("
        UPDATE Player
        SET money = money - ?
        WHERE player_id = ?
    ");
    $stmt->bind_param("ii", $cost, $playerId);
    $stmt->execute();

    $db->commit();

    echo json_encode([
        'success' => true,
        'house_count' => $houseCount,
        'hotel_count' => $hotelCount,
        'cost' => $cost,
        'message' => $message
    ]);

} catch (Throwable $e) {
    $db->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
