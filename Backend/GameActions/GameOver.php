<?php
session_start();
require_once __DIR__ . "/../../Database/Database.php";
header("Content-Type: application/json");

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) $data = $_POST;

$gameId = (int)($data["gameId"] ?? 0);
if ($gameId <= 0) {
  echo json_encode(["success" => false, "message" => "Missing gameId"]);
  exit;
}

try {
  $db->begin_transaction();

  // Mark game completed
  $g = $db->prepare("UPDATE Game SET status='completed', last_saved_time=NOW() WHERE game_id=?");
  $g->bind_param("i", $gameId);
  $g->execute();
  $g->close();

  // Get players + wallet worth
  $stmt = $db->prepare("
    SELECT 
      p.player_id,
      p.player_name,
      p.money,
      COALESCE(w.propertyWorthCash, 0) AS propertyWorthCash
    FROM Player p
    LEFT JOIN Wallet w ON w.player_id = p.player_id
    WHERE p.current_game_id = ?
  ");
  $stmt->bind_param("i", $gameId);
  $stmt->execute();
  $res = $stmt->get_result();

  $players = [];
  while ($row = $res->fetch_assoc()) {
    $row["player_id"] = (int)$row["player_id"];
    $row["money"] = (int)$row["money"];
    $row["propertyWorthCash"] = (int)$row["propertyWorthCash"];
    $row["assets"] = $row["money"] + $row["propertyWorthCash"];
    $players[] = $row;
  }
  $stmt->close();

  // Sort: assets desc, money desc
  usort($players, function($a, $b) {
    if ($b["assets"] !== $a["assets"]) return $b["assets"] <=> $a["assets"];
    return $b["money"] <=> $a["money"];
  });

  // Log
  $winnerName = $players[0]["player_name"] ?? ("Player " . ($players[0]["player_id"] ?? ""));
  $desc = "Game ended manually. Winner: {$winnerName}.";
  $l = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
  $l->bind_param("is", $gameId, $desc);
  $l->execute();
  $l->close();

  $db->commit();

  // Return leaderboard with ranks
  $leaderboard = [];
  foreach ($players as $i => $p) {
    $leaderboard[] = [
      "rank" => $i + 1,
      "player_id" => (int)$p["player_id"],
      "player_name" => $p["player_name"],
      "money" => (int)$p["money"],
      "propertyWorthCash" => (int)$p["propertyWorthCash"],
      "assets" => (int)$p["assets"]
    ];
  }

  echo json_encode([
    "success" => true,
    "gameOver" => true,
    "leaderboard" => $leaderboard
  ]);
} catch (Exception $e) {
  $db->rollback();
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
