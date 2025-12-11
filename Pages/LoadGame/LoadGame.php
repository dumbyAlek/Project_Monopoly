<?php
require_once "../../Database/Database.php";
session_start();

$db = Database::getInstance()->getConnection();
if (!$db) die("DB connection failed.");

// Optional: filter by user
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

$query = "SELECT g.game_id, g.last_saved_time, g.status, g.passing_GO, b.total_funds AS bank_funds
          FROM Game g
          LEFT JOIN Bank b ON g.game_id = b.bank_id";

if ($user_id !== null) {
    $query .= " WHERE g.user_id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $db->prepare($query);
}

if (!$stmt->execute()) die("Query failed: " . $stmt->error);
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Load Saved Games</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
        tr:hover { background: #f1f1f1; }
        .load-btn { padding: 4px 8px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
<h1>Load Saved Games</h1>
<table>
    <thead>
        <tr>
            <th>Game ID</th>
            <th>Players</th>
            <th>Last Saved</th>
            <th>Bank Funds</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($game = $result->fetch_assoc()): ?>
        <?php
            // Get players and their money
            $pidQuery = $db->prepare("SELECT player_name, money FROM Player WHERE current_game_id = ?");
            $pidQuery->bind_param("i", $game['game_id']);
            $pidQuery->execute();
            $playersResult = $pidQuery->get_result();
            $players = [];
            while ($p = $playersResult->fetch_assoc()) {
                $players[] = $p['player_name'] . " ($" . $p['money'] . ")";
            }
            $pidQuery->close();

            $bankMoney = $game['bank_funds'] ?? 0;
        ?>
        <tr>
            <td><?= htmlspecialchars($game['game_id']) ?></td>
            <td><?= implode(", ", $players) ?></td>
            <td><?= $game['last_saved_time'] ?? 'N/A' ?></td>
            <td>$<?= $bankMoney ?></td>
            <td>
                <form action="../GamePage/GamePage.php" method="GET">
                    <input type="hidden" name="game_id" value="<?= $game['game_id'] ?>">
                    <button class="load-btn" type="submit">Load</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>
