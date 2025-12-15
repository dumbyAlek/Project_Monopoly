<?php
require_once "../../Database/Database.php";
session_start();

// DB connection
$db = Database::getInstance()->getConnection();
if (!$db) die("DB connection failed.");

// Optional: filter by user
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// Fetch saved games
$query = "SELECT g.game_id, g.last_saved_time, g.status, g.passing_GO, b.total_funds AS bank_funds
          FROM Game g
          LEFT JOIN Bank b ON g.game_id = b.game_id";

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Load Saved Games</title>
    <style>
        :root {
            --accent: #4caf50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url("../../Assets/bg.webp") no-repeat center center/cover;
        }

        .container {
            width: 90%;
            max-width: 900px;
            padding: 30px;
            background: rgba(114, 134, 96, 0.55);
            color: #fff;
            border-radius: 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.25);
        }

        h1 {
            text-align: center;
            margin-bottom: 24px;
            font-size: 32px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(6px);
        }

        th, td {
            padding: 12px 16px;
            text-align: left;
        }

        th {
            background: rgba(255, 255, 255, 0.15);
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.05);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        .action-btn {
            padding: 8px 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
            font-weight: bold;
        }

        .load-btn {
            background: var(--accent);
            color: #fff;
        }

        .load-btn:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: #f44336;
            color: #fff;
        }

        .delete-btn:hover {
            opacity: 0.85;
            transform: translateY(-1px);
        }

        form {
            margin: 0;
        }

        @media (max-width: 720px) {
            .container {
                padding: 20px;
            }

            table th, table td {
                padding: 10px 12px;
            }

            h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Load Saved Games</h1>
        <table>
            <thead>
                <tr>
                    <th>Game ID</th>
                    <th>Players</th>
                    <th>Last Saved</th>
                    <th>Bank Funds</th>
                    <th>Load</th>
                    <th>Delete</th>
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
                        $players[] = htmlspecialchars($p['player_name']) . " ($" . htmlspecialchars($p['money']) . ")";
                    }
                    $pidQuery->close();

                    $bankMoney = $game['bank_funds'] ?? 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($game['game_id']) ?></td>
                    <td><?= implode(", ", $players) ?></td>
                    <td><?= $game['last_saved_time'] ?? 'N/A' ?></td>
                    <td>$<?= htmlspecialchars($bankMoney) ?></td>

                    <!-- Load Button -->
                    <td>
                        <form action="../GamePage/GamePage.php" method="GET">
                            <input type="hidden" name="game_id" value="<?= htmlspecialchars($game['game_id']) ?>">
                            <button class="action-btn load-btn" type="submit">Load</button>
                        </form>
                    </td>

                    <!-- Delete Button -->
                    <td>
                        <form action="../DeleteGame/delete.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this game?');">
                            <input type="hidden" name="game_id" value="<?= htmlspecialchars($game['game_id']) ?>">
                            <button class="action-btn delete-btn" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
