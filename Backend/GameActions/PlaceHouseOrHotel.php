<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
  echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
  exit;
}

$db = Database::getInstance()->getConnection();

$playerId   = (int)$data['playerId'];
$propertyId = (int)$data['propertyId'];
$houses     = (int)$data['house_count'];
$hasHotel   = (int)$data['has_hotel'];

try {
  $db->begin_transaction();

  $stmt = $db->prepare("
    SELECT owner_id 
    FROM Property 
    WHERE property_id = ?
    FOR UPDATE
  ");
  $stmt->bind_param("i", $propertyId);
  $stmt->execute();
  $prop = $stmt->get_result()->fetch_assoc();

  if (!$prop || (int)$prop['owner_id'] !== $playerId) {
    throw new Exception("Player does not own this property");
  }

  $stmt = $db->prepare("
    UPDATE Property
    SET house_count = ?, has_hotel = ?
    WHERE property_id = ?
  ");
  $stmt->bind_param("iii", $houses, $hasHotel, $propertyId);
  $stmt->execute();

  $db->commit();
  echo json_encode(['success' => true]);

} catch (Throwable $e) {
  $db->rollback();
  echo json_encode([
    'success' => false,
    'message' => $e->getMessage()
  ]);
}
