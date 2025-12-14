<?php
// checkPropertyStatus.php
require_once "../../Database/Database.php";

// Get the JSON payload
$input = json_decode(file_get_contents('php://input'), true);
$propertyId = isset($input['propertyId']) ? intval($input['propertyId']) : null;

header('Content-Type: application/json');

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
        SELECT property_id, price, rent, house_count, hotel_count, is_mortgaged, owner_id 
        FROM Property
        WHERE property_id = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        echo json_encode([
            "success" => false,
            "message" => "Property not found"
        ]);
        exit;
    }

    // Return property info
    echo json_encode([
        "success" => true,
        "property_id" => intval($property['property_id']),
        "price" => intval($property['price']),
        "rent" => intval($property['rent']),
        "house_count" => intval($property['house_count']),
        "hotel_count" => intval($property['hotel_count']),
        "is_mortgaged" => boolval($property['is_mortgaged']),
        "owner_id" => $property['owner_id'] !== null ? intval($property['owner_id']) : null
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}