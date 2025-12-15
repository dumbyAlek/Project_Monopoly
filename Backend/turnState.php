<?php
require_once __DIR__ . '/../Database/Database.php';
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $gameId = isset($_GET['game_id']) ? (int)$_GET['game_id'] : 0;
    if ($gameId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing game_id']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT description
        FROM Log
        WHERE game_id = ? AND description LIKE 'TURN_CURRENT:%'
        ORDER BY timestamp DESC, log_id DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode(['success' => true, 'currentPlayerId' => null]);
        exit;
    }

    $desc = $row['description'];
    $parts = explode(':', $desc);
    $playerId = isset($parts[1]) ? (int)$parts[1] : null;

    echo json_encode(['success' => true, 'currentPlayerId' => $playerId]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data || !isset($data['gameId'], $data['currentPlayerId'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }

    $gameId = (int)$data['gameId'];
    $playerId = (int)$data['currentPlayerId'];

    $desc = "TURN_CURRENT:" . $playerId;

    $stmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $gameId, $desc);
    $stmt->execute();

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported method']);
