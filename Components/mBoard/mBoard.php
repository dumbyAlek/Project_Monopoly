<?php
    // Placeholder for PHP logic (load players, bank, etc.)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monopoly Board</title>
  <link rel="stylesheet" href="../../Components/mBoard/mBoard.css">
</head>
<body>

  <div class="board" id="board">
    <!-- Dice Container -->
    <div class="dice-container dice-area">
      <div class="dice" id="dice1">🎲</div>
      <div class="dice" id="dice2">🎲</div>
      <button id="rollBtn">Roll Dice</button>
      <p id="diceResult" class="result"></p>
    </div>

    <!-- Chance & Community Boxes -->
    <div class="chance-box">Chance</div>
    <div class="community-box">Community Chest</div>
  </div>

  <script type="module" src="../../Components/mBoard/mBoard.js"></script>
</body>
</html>
