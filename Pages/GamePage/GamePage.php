<?php
    // PHP logic for players/bank goes here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monopoly Game</title>
    <link rel="stylesheet" href="GamePage.css">
</head>
<body>
    <div class="game-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <h2>Bank</h2>
            <p class="bank-money">Bank Money: $10,000</p>

            <h2>Players</h2>
            <div class="player-info">
                <p>Player 1: $1,500</p>
                <p>Player 2: $1,500</p>
                <p>Player 3: $1,500</p>
                <p>Player 4: $1,500</p>
            </div>
        </aside>

        <!-- Board Area -->
        <main class="board-container">
            <div class="board-block">
                <?php include "../../Components/mBoard/mBoard.php"; ?>
            </div>
        </main>
    </div>

    <script src="GamePage.js"></script>
</body>
</html>
