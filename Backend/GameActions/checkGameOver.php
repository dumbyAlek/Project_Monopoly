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
  // Pull all players + wallet worth
  $stmt = $db->prepare("
    SELECT 
      p.player_id, p.player_name, p.money,
      COALESCE(w.propertyWorthCash, 0) AS propertyWorthCash
    FROM Player p
    LEFT JOIN Wallet w ON w.player_id = p.player_id
    WHERE p.current_game_id = ?
  ");
  $stmt->bind_param("i", $gameId);
  $stmt->execute();
  $res = $stmt->get_result();

  $players = [];
  $bankrupt = [];
  while ($row = $res->fetch_assoc()) {
    $pid = (int)$row["player_id"];
    $money = (int)$row["money"];
    $worth = (int)$row["propertyWorthCash"];
    $assets = $money + $worth;

    $row["money"] = $money;
    $row["propertyWorthCash"] = $worth;
    $row["assets"] = $assets;

    $players[] = $row;

    if ($money <= 0 && $worth <= 0) {
      $bankrupt[] = $pid;
    }
  }
  $stmt->close();

  // If nobody bankrupt => game continues
  if (count($bankrupt) === 0) {
    echo json_encode(["success" => true, "gameOver" => false]);
    exit;
  }

  // Rank by total assets (money + propertyWorthCash), tie-breaker money
  usort($players, function($a, $b) {
    if ($b["assets"] !== $a["assets"]) return $b["assets"] <=> $a["assets"];
    return $b["money"] <=> $a["money"];
  });

  // Mark game completed (only once)
  $g = $db->prepare("UPDATE Game SET status='completed', last_saved_time=NOW() WHERE game_id=?");
  $g->bind_param("i", $gameId);
  $g->execute();
  $g->close();

  // Log summary
  $winner = $players[0]["player_name"] ?? ("Player " . ($players[0]["player_id"] ?? ""));
  $loserNames = [];
  foreach ($players as $p) {
    if ((int)$p["money"] <= 0 && (int)$p["propertyWorthCash"] <= 0) {
      $loserNames[] = $p["player_name"] ?? ("Player ".$p["player_id"]);
    }
  }
  $loserText = count($loserNames) ? (" Loser(s): " . implode(", ", $loserNames)) : "";

  $desc = "GAME OVER. Winner: {$winner}." . $loserText;
  $l = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
  $l->bind_param("is", $gameId, $desc);
  $l->execute();
  $l->close();

  echo json_encode([
    "success" => true,
    "gameOver" => true,
    "bankruptPlayerIds" => $bankrupt,
    "ranking" => array_map(function($p, $idx) {
      return [
        "rank" => $idx + 1,
        "player_id" => (int)$p["player_id"],
        "player_name" => $p["player_name"],
        "money" => (int)$p["money"],
        "propertyWorthCash" => (int)$p["propertyWorthCash"],
        "assets" => (int)$p["assets"],
        "isLoser" => ((int)$p["money"] <= 0 && (int)$p["propertyWorthCash"] <= 0)
      ];
    }, $players, array_keys($players))
  ]);
} catch (Exception $e) {
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
