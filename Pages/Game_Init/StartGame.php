<?php require_once "../../Database/Database.php"; ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Start New Game</title>
    <link rel="stylesheet" href="init.css">
</head>
<body>

<h1>Start New Game</h1>

<div class="rules-box">
    <h3>Game Rules</h3>
    <p>• Each player starts with the chosen player money.</p>
    <p>• Bank starts with the chosen bank fund and services the players.</p>
    <p>• Last player not bankrupt wins.</p>
</div>

<form id="gameForm" action="process_game.php" method="POST">
    <div class="controls">
        <label>Starting Bank Fund:
            <input type="number" name="starting_bank_fund" id="starting_bank_fund" value="100000" min="0" required>
        </label>

        <label>Starting Players Money:
            <input type="number" name="starting_player_money" id="starting_player_money" value="1500" min="0" required>
        </label>
    </div>

    <div class="players-container">

        <div class="player-row" data-slot="1">
            <div class="player-label">Player 1 <span class="icon">🏰</span></div>
            <div class="dropzone">
                <input class="name-field" draggable="true" type="text" name="player1" placeholder="Enter name (P1)">
            </div>
        </div>

        <div class="player-row" data-slot="2">
            <div class="player-label">Player 2 <span class="icon">🚗</span></div>
            <div class="dropzone">
                <input class="name-field" draggable="true" type="text" name="player2" placeholder="Enter name (P2)">
            </div>
        </div>

        <div class="player-row" data-slot="3">
            <div class="player-label">Player 3 <span class="icon">🛵</span></div>
            <div class="dropzone">
                <input class="name-field" draggable="true" type="text" name="player3" placeholder="Enter name (P3)">
            </div>
        </div>

        <div class="player-row" data-slot="4">
            <div class="player-label">Player 4 <span class="icon">🎩</span></div>
            <div class="dropzone">
                <input class="name-field" draggable="true" type="text" name="player4" placeholder="Enter name (P4)">
            </div>
        </div>

    </div>

    <div class="form-actions">
        <button type="submit">Start Game</button>
        <button type="button" onclick="location.href='saved_games.php'">View Saved Games</button>
    </div>
</form>

<script src="init.js"></script>
</body>
</html>
