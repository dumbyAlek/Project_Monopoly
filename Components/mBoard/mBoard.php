<?php
    // PHP: load players, bank, etc. here if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Monopoly Board</title>

  <!-- Your board CSS -->
  <link rel="stylesheet" href="../../Components/mBoard/mBoard.css">

  <style>
    /* ===== CARD MODAL STYLES ===== */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
    }
    .modal-overlay.active {
        display: flex;
    }

    .card-modal {
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        text-align: center;
        max-width: 450px;
        width: 90%;
        animation: cardFlip 0.5s ease-out;
    }
    @keyframes cardFlip {
        0% {
            transform: rotateY(90deg) scale(0.5);
            opacity: 0;
        }
        100% {
            transform: rotateY(0deg) scale(1);
            opacity: 1;
        }
    }

    .card-modal.community-chest {
        border: 5px solid #87CEEB;
        background: linear-gradient(to bottom, #B0E0E6 0%, white 20%);
    }
    .card-modal.chance {
        border: 5px solid #FF6B6B;
        background: linear-gradient(to bottom, #FFB6B6 0%, white 20%);
    }

    .card-header {
        font-size: 32px;
        font-weight: bold;
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 10px;
    }
    .community-chest .card-header {
        background-color: #87CEEB;
        color: #000;
    }
    .chance .card-header {
        background-color: #FF6B6B;
        color: white;
    }

    .card-icon {
        margin: 20px 0;
    }
    .card-icon img {
        width: 140px;
        height: auto;
        display: block;
        margin: 0 auto;
    }

    .card-text {
        font-size: 22px;
        font-weight: bold;
        color: #333;
        padding: 20px;
        background-color: rgba(255,255,255,0.9);
        border-radius: 10px;
        min-height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
    }

    .close-btn {
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 15px 40px;
        font-size: 18px;
        font-weight: bold;
        border-radius: 10px;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    .close-btn:hover {
        background-color: #45a049;
    }
  </style>
</head>
<body>

  <div class="board" id="board">
    <!-- Dice Container -->
    <div class="dice-container dice-area">
      <div class="dice" id="dice1">🎲</div>
      <div class="dice" id="dice2">🎲</div>
      <button id="rollBtn">Roll Dice</button>
      <button id="endTurnBtn" class="hidden">End Turn</button>
      <p id="diceResult" class="result"></p>
    </div>

    <!-- Chance & Community Boxes (clickable) -->
    <div class="chance-box" id="chanceBox">Chance</div>
    <div class="community-box" id="communityBox">Community Chest</div>
  </div>

  <!-- ===== CARD MODAL HTML ===== -->
  <div class="modal-overlay" id="cardModal" onclick="closeCard()">
      <div class="card-modal" id="cardContent" onclick="event.stopPropagation()">
          <!-- JS injects the card here -->
      </div>
  </div>

  <!-- Your board JS -->
<script type="module" src="../../Components/mBoard/mBoard.js"></script>

<!-- Card logic JS -->
<script type="module">
    import { showCommunityChest, showChance, closeCard } from '../../Components/mBoard/mBoardCards.js';

    document.getElementById('communityBox').addEventListener('click', showCommunityChest);
    document.getElementById('chanceBox').addEventListener('click', showChance);

    // make closeCard global for inline button
    window.closeCard = closeCard;
</script>
</body>
</html>
