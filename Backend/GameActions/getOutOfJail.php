<?php
session_start();
require_once "../../Database/Database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$player_id = isset($_POST['player_id']) ? intval($_POST['player_id']) : null;
$useCard = isset($_POST['use_card']) ? boolval($_POST['use_card']) : false;

if (!$player_id) {
    echo json_encode(['success' => false, 'message' => 'Player ID missing']);
    exit;
}

$db = Database::getInstance()->getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

// Fetch player info
$stmt = $db->prepare("SELECT is_in_jail, has_get_out_card, money, current_game_id FROM Player WHERE player_id = ?");
$stmt->execute([$player_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo json_encode(['success' => false, 'message' => 'Player not found']);
    exit;
}

if (!$player['is_in_jail']) {
    echo json_encode(['success' => false, 'message' => 'Player is not in jail']);
    exit;
}

// Fetch passing_GO amount from the game
$stmt = $db->prepare("SELECT passing_GO FROM Game WHERE game_id = ?");
$stmt->execute([$player['current_game_id']]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);
$fine = $game ? intval($game['passing_GO']) : 50; // default to 50 if not found

$updated = false;

if ($useCard && $player['has_get_out_card']) {
    $stmt = $db->prepare("UPDATE Player SET is_in_jail = 0, has_get_out_card = 0 WHERE player_id = ?");
    $updated = $stmt->execute([$player_id]);
} elseif (!$useCard && $player['money'] >= $fine) {
    $stmt = $db->prepare("UPDATE Player SET is_in_jail = 0, money = money - ? WHERE player_id = ?");
    $updated = $stmt->execute([$fine, $player_id]);
} else {
    echo json_encode(['success' => false, 'message' => 'Not enough funds or no Get Out of Jail card']);
    exit;
}

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Player released from jail', 'fine_paid' => !$useCard ? $fine : 0]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
