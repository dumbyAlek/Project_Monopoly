<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['playerId'], $data['position'])) {
  echo json_encode(["success"=>false, "message"=>"Invalid input"]);
  exit;
}

$playerId = (int)$data['playerId'];
$pos = (int)$data['position'];

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("UPDATE Player SET position = ? WHERE player_id = ?");
$stmt->bind_param("ii", $pos, $playerId);
$stmt->execute();

echo json_encode(["success"=>true]);