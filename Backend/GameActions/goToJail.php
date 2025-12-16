<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

$input = json_decode(file_get_contents("php://input"), true);
if (!is_array($input)) $input = $_POST;

$playerId = isset($input['playerId']) ? (int)$input['playerId'] : (isset($input['player_id']) ? (int)$input['player_id'] : 0);
$gameId   = isset($input['gameId']) ? (int)$input['gameId'] : (isset($input['game_id']) ? (int)$input['game_id'] : 0);

if ($playerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'playerId missing']);
    exit;
}

try {
    $db->begin_transaction();

    // Lock player row
    $pStmt = $db->prepare("
        SELECT player_id, is_in_jail, current_game_id
        FROM Player
        WHERE player_id = ? 
        FOR UPDATE
    ");
    $pStmt->bind_param("i", $playerId);
    $pStmt->execute();
    $player = $pStmt->get_result()->fetch_assoc();
    $pStmt->close();

    if (!$player) throw new Exception("Player not found");

    $effectiveGameId = $gameId > 0 ? $gameId : (int)$player['current_game_id'];
    if ($effectiveGameId <= 0) throw new Exception("Game not found for player");

    // Update player: set in_jail
    $uStmt = $db->prepare("
        UPDATE Player
        SET is_in_jail = 1, position = ?
        WHERE player_id = ?
    ");
    // Find jail tile index
    $jailIndexStmt = $db->prepare("SELECT tile_index FROM Tile WHERE category = 'jail' AND game_id = ?");
    $jailIndexStmt->bind_param("i", $effectiveGameId);
    $jailIndexStmt->execute();
    $res = $jailIndexStmt->get_result()->fetch_assoc();
    $jailIndexStmt->close();

    $jailIndex = $res['tile_index'] ?? 10; // fallback to tile 10 if missing
    $uStmt->bind_param("ii", $jailIndex, $playerId);
    $uStmt->execute();
    $uStmt->close();

    // Log action
    $logTxt = "Player {$playerId} sent to jail.";
    $lStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $lStmt->bind_param("is", $effectiveGameId, $logTxt);
    $lStmt->execute();
    $lStmt->close();

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Player sent to jail',
        'jailIndex' => $jailIndex
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
