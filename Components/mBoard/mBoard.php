<!DOCTYPE html>

  <link rel="stylesheet" href="../../Components/mBoard/mBoard.css">


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
  

<script type="module" src="../../Components/mBoard/mBoard.js"></script>
