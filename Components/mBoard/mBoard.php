<!-- mBoard.php  -->
<?php
    // Placeholder for PHP logic (load players, bank, etc.)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monopoly Board</title>
    <link rel="stylesheet" href="../../Components/mBoard/tiles.css">
  <link rel="stylesheet" href="../../Components/mBoard/mBoard.css">
</head>
<body>

  <!-- Monopoly Board Container -->
  <div class="board" id="board">
    <!-- Dice Container (center panel) -->
    <div class="dice-container dice-area">
      <h3>🎲 Dice Roll</h3>
      <button id="rollBtn">Roll Dice</button>
      <p id="diceResult"></p>
    </div>
      <div class="chance-box">CB</div>
      <div class="community-box">CCB</div>
  </div>

  

  <!-- Main Logic -->
  <script type="module"
  src="../../Components/mBoard/mBoard.js"></script>

</body>
</html>
