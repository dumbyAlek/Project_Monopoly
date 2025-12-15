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

  <!-- ===== CARD LOGIC JS ===== -->
  <script>
    // ------- Community Chest Cards -------
    const communityChestCards = [
        { text: "DOCTOR'S FEE<br>PAY 50 Taka", action: "pay", amount: 50 },
        { text: "BANK ERROR IN YOUR FAVOR<br>COLLECT 200 Taka", action: "collect", amount: 200 },
        { text: "PAY SCHOOL FEES<br>PAY 150 Taka", action: "pay", amount: 150 },
        { text: "FROM SALE OF STOCK<br>YOU GET 45 Taka", action: "collect", amount: 45 },
        { text: "HOLIDAY FUND MATURES<br>RECEIVE 100 Taka", action: "collect", amount: 100 },
        { text: "INCOME TAX REFUND<br>COLLECT 20 Taka", action: "collect", amount: 20 },
        { text: "LIFE INSURANCE MATURES<br>COLLECT 100 Taka", action: "collect", amount: 100 },
        { text: "PAY HOSPITAL FEES<br>PAY 100 Taka", action: "pay", amount: 100 },
        { text: "GET OUT OF JAIL FREE<br>Keep this card", action: "jail_free", amount: 0 },
        { text: "YOU INHERIT 100 Taka<br>COLLECT 100 Taka", action: "collect", amount: 100 }
    ];

    // ------- Chance Cards -------
    const chanceCards = [
        { text: "ADVANCE TO GO<br>COLLECT 200 Taka", action: "advance_go", amount: 200 },
        { text: "GO TO JAIL<br>Do not pass GO", action: "go_jail", amount: 0 },
        { text: "PAY POOR TAX<br>PAY 15 Taka", action: "pay", amount: 15 },
        { text: "TAKE A TRIP TO READING RAILROAD<br>If you pass GO collect 200 Taka", action: "move_railroad", amount: 0 },
        { text: "BANK PAYS YOU DIVIDEND<br>COLLECT 50 Taka", action: "collect", amount: 50 },
        { text: "GET OUT OF JAIL FREE<br>Keep this card", action: "jail_free", amount: 0 },
        { text: "GO BACK 3 SPACES<br>Move back 3 spaces", action: "go_back", amount: 3 },
        { text: "SPEEDING FINE<br>PAY 15 Taka", action: "pay", amount: 15 },
        { text: "ADVANCE TO BOARDWALK<br>Collect 200 if you pass GO", action: "advance_boardwalk", amount: 0 },
        { text: "YOU HAVE WON A CROSSWORD COMPETITION<br>COLLECT 100 Taka", action: "collect", amount: 100 }
    ];

    // ------ Hooks to your board tiles ------
    document.getElementById('communityBox').addEventListener('click', showCommunityChest);
    document.getElementById('chanceBox').addEventListener('click', showChance);

    // If you want to trigger from JS when a player lands:
    //   showCommunityChest();
    //   showChance();

    function showCommunityChest() {
        const randomCard = communityChestCards[Math.floor(Math.random() * communityChestCards.length)];
        displayCard('community-chest', 'Community Chest', randomCard);
    }

    function showChance() {
        const randomCard = chanceCards[Math.floor(Math.random() * chanceCards.length)];
        displayCard('chance', 'Chance', randomCard);
    }

    function displayCard(type, title, cardData) {
        const modal   = document.getElementById('cardModal');
        const content = document.getElementById('cardContent');

        // choose the correct image
        const iconHtml = (type === 'community-chest')
            ? '<div class="card-icon"><img src="../../Assets/chest.png" alt="Chest"></div>'
            : '<div class="card-icon"><img src="../../Assets/chance.png" alt="Chance"></div>'; // or another icon

        content.className = `card-modal ${type}`;
        content.innerHTML = `
            <div class="card-header">${title}</div>
            ${iconHtml}
            <div class="card-text">${cardData.text}</div>
            <button class="close-btn" onclick="closeCard()">OK</button>
        `;

        modal.classList.add('active');

        // hook into your game logic
        applyCardAction(cardData);
    }

    function closeCard() {
        document.getElementById('cardModal').classList.remove('active');
    }

    function applyCardAction(cardData) {
        console.log('Card Action:', cardData.action, 'Amount:', cardData.amount);

        // TODO: replace these with your real functions
        switch(cardData.action) {
            case 'collect':
                // updatePlayerMoney(currentPlayer, cardData.amount);
                break;
            case 'pay':
                // updatePlayerMoney(currentPlayer, -cardData.amount);
                break;
            case 'jail_free':
                // giveJailFreeCard(currentPlayer);
                break;
            case 'go_jail':
                // sendToJail(currentPlayer);
                break;
            case 'advance_go':
                // movePlayerToGo(currentPlayer);
                break;
            case 'go_back':
                // movePlayerBack(currentPlayer, cardData.amount);
                break;
            case 'move_railroad':
                // movePlayerToRailroad(currentPlayer);
                break;
            case 'advance_boardwalk':
                // movePlayerToBoardwalk(currentPlayer);
                break;
        }
    }

    // Close with Esc
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeCard();
    });
  </script>
</body>
</html>
