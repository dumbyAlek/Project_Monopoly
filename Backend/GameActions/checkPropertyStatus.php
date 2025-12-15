<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

// Get the JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$propertyId = isset($input['propertyId']) ? intval($input['propertyId']) : null;
$gameId = isset($input['gameId']) ? intval($input['gameId']) : null;

if ($input === null) {
    echo json_encode(["success" => false, "message" => "Invalid JSON body"]);
    exit;
}

if ($gameId === null) {
    echo json_encode(["success" => false, "message" => "Game ID not provided"]);
    exit;
}

if ($propertyId === null) {
    echo json_encode([
        "success" => false,
        "message" => "Property ID not provided"
    ]);
    exit;
}

$db = Database::getInstance()->getConnection();
if (!$db) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit;
}

try {
    // Fetch property info
    $stmt = $db->prepare("
        SELECT property_id, price, rent, house_count, hotel_count, is_mortgaged, owner_id, current_game_id
        FROM Property
        WHERE property_id = ? AND current_game_id = ?
    ");
    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $db->error]);
        exit;
    }

    $stmt->bind_param("ii", $propertyId, $gameId);
    $stmt->execute();

    $result = $stmt->get_result();
    $property = $result->fetch_assoc();
    $stmt->close();

    if (!$property) {
        echo json_encode(["success" => false, "message" => "Property not found"]);
        exit;
    }

    echo json_encode([
            "success" => true,
            "property_id" => (int)$property["property_id"],
            "price" => (int)$property["price"],
            "rent" => (int)$property["rent"],
            "house_count" => (int)$property["house_count"],
            "hotel_count" => (int)$property["hotel_count"],
            "is_mortgaged" => (bool)$property["is_mortgaged"],
            "owner_id" => $property["owner_id"] !== null ? (int)$property["owner_id"] : null,
            "current_game_id" => (int)$property["current_game_id"]
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => "Server error: " . $e->getMessage()
        ]);
        exit;
    }

?>