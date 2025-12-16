<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';
require_once __DIR__ . '/DataFacade.php';

$db = Database::getInstance()->getConnection();
$currentGameId = $_GET['game_id'] ?? 1;

$dataFacade = new DataFacade($db, $currentGameId);

// Fetch bank and players using facade
$bank = $dataFacade->getBank();
$players = $dataFacade->getPlayers();
$properties = $dataFacade->getProperties();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monopoly Game</title>
    <link rel="stylesheet" href="GamePage.css">
</head>
<style>
    /* ===========================
   Left Sidebar Buttons
   =========================== */
.left-sidebar .bank-panel button {
    display: block;
    width: 90%;
    margin: 10px auto;
    padding: 12px 0;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #4caf50, #43a047);
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.left-sidebar .bank-panel button:hover {
    background: linear-gradient(135deg, #43a047, #388e3c);
    transform: translateY(-2px);
    box-shadow: 0 6px 10px rgba(0,0,0,0.15);
}

.left-sidebar .bank-panel button:active {
    transform: translateY(1px);
    box-shadow: 0 3px 5px rgba(0,0,0,0.2);
}

/* Optional: Different colors for specific buttons */
#save-game-btn {
    background: linear-gradient(135deg, #2196f3, #1976d2);
}

#save-game-btn:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0);
}

.left-sidebar .bank-panel button:nth-of-type(2) { /* Start New Game */
    background: linear-gradient(135deg, #ff9800, #fb8c00);
}

.left-sidebar .bank-panel button:nth-of-type(3) { /* Home */
    background: linear-gradient(135deg, #9c27b0, #7b1fa2);
}

.left-sidebar .bank-panel button:nth-of-type(4) { /* Log Out */
    background: linear-gradient(135deg, #f44336, #d32f2f);
}

</style>
<body>
    <script>
        window.currentGameId = <?php echo json_encode($currentGameId); ?>;
        window.playersData = <?php echo json_encode(array_map(fn($p)=>[
            "player_id" => (int)$p["player_id"],
            "name" => $p["player_name"],
            "position" => (int)$p["position"],
            "is_in_jail" => (bool)$p["is_in_jail"],
            "has_get_out_card" => (bool)$p["has_get_out_card"]
        ], $players)); ?>;
        window.gameProperties = <?php echo json_encode($properties ?? [], JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <div class="game-container">
    <!-- Left Sidebar: Bank -->
    <aside class="sidebar left-sidebar">

        <div class="bank-panel">
            <img src="../../Assets/counter1.webp" alt="Cash Counter" class="cash-counter">

            <p class="bank-money">Bank Money: $<?php echo $bank['total_funds']; ?></p>
            <p class="bank-mortgages">Mortgages: $0</p>
            <p class="bank-loans">Loans: $0</p>

            <button id="save-game-btn">Save Game</button>

            <hr class="panel-divider">

            <!-- NEW BUTTONS -->
            <button onclick="window.location.href='../Game_Init/StartGame.php'">Start New Game</button>

            <button onclick="window.location.href='../HomePage/HomePage.php'">Home</button>

            <button onclick="window.location.href='../../index.php'">Log Out</button>
        </div>
    </aside>
    <!-- Board Area -->
    <main class="board-container">
        <div class="board-block">
            <?php include "../../Components/mBoard/mBoard.php"; ?>
        </div>
    </main>

    <!-- BUY PROPERTY FROM PLAYER MODAL -->
    <div id="tradeModal" class="modal" style="display:none;">
        <div class="modal-content">

            <h3 id="tradeTitle">Property Trade</h3>

            <!-- BUYER VIEW -->
            <div id="buyerView">
                <p><strong>Buyer:</strong> <span id="buyerName"></span></p>
                <p>Propose Amount</p>

                <input type="number" id="offerAmount" min="1" required />

                <div class="modal-actions">
                    <button id="proceedBtn" disabled>Proceed to Buying</button>
                    <button onclick="closeTradeModal()">Cancel</button>
                </div>
            </div>

            <!-- OWNER VIEW -->
            <div id="ownerView" style="display:none;">
                <p><strong>Owner:</strong> <span id="ownerName"></span></p>
                <p>
                    Offered Price:
                    <strong>$<span id="offeredPrice"></span></strong>
                </p>

                <div class="modal-actions">
                    <button id="acceptTradeBtn">Accept</button>
                    <button id="declineTradeBtn">Decline</button>
                </div>
            </div>

            <!-- RESULT VIEW -->
            <div id="resultView" style="display:none;">
                <p id="resultMessage"></p>
                <button onclick="closeTradeModal()">Close</button>
            </div>

        </div>
    </div>

    <!-- SELL PROPERTY TO PLAYER MODAL -->
    <div id="sellTradeModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h3 id="sellModalTitle">Sell Property</h3>

        <!-- STEP 1: OPTIONS -->
        <div id="sellOptionsView">
            <p id="sellPropertyLabel"></p>
            <div class="modal-actions">
                <button id="sellToBankBtn">Sell to Bank</button>
                <button id="sellToPlayerBtn">Sell to Player</button>
                <button onclick="closeSellTradeModal()">Cancel</button>
            </div>
        </div>

        <!-- STEP 2: SELL TO PLAYER FORM -->
        <div id="sellToPlayerFormView" style="display:none;">
            <p><strong>Owner:</strong> <span id="sellerName"></span></p>
            <label>Asking Price</label>
            <input type="number" id="askingPrice" min="1" />
            <label>Choose Player</label>
            <select id="sellBuyerSelect">
                <option value="">-- Select buyer --</option>
            </select>

        <div class="modal-actions">
            <button id="proceedToBuyerBtn" disabled>Proceed</button>
            <button id="backToSellOptionsBtn">Back</button>
            <button onclick="closeSellTradeModal()">Cancel</button>
        </div>
        </div>

        <!-- BUYER CONFIRM VIEW (keep) -->
        <div id="buyerConfirmView" style="display:none;">
            <p><strong>Buyer:</strong> <span id="buyerConfirmName"></span></p>
            <p>Asking Price: <strong>$<span id="finalPrice"></span></strong></p>

            <div class="modal-actions">
                <button id="acceptSellBtn">Accept</button>
                <button id="declineSellBtn">Decline</button>
            </div>
        </div>


        <!-- RESULT (keep) -->
        <div id="sellResultView" style="display:none;">
        <p id="sellResultMessage"></p>
        <button onclick="closeSellTradeModal()">Close</button>
        </div>
    </div>
    </div>


    <aside class="sidebar right-sidebar">
        <div class="players-container">
            <?php foreach($players as $p): ?>
                <div class="player-panel" data-player-id="<?php echo $p['player_id']; ?>">
                    <!-- <h3><?php echo htmlspecialchars($p['player_name']); ?> <img src="../../Assets/player-icon.png" alt="icon" class="player-icon"></h3> -->
                    <p>Money: $<span class="money-value"><?php echo $p['money']; ?></span></p>
                    <p>Properties: <?php echo $p['number_of_properties']; ?> ($<?php echo $p['propertyWorthCash']; ?>)</p>
                    <p>Get Out of Jail Card: <?php echo $p['has_get_out_card'] ? "Yes" : "No"; ?></p>
                    <!-- <p>Loan: $0 <button class="pay-loan-btn">Pay</button></p>
                    <p>Debt from Players: $<?php echo $p['debt_from_players']; ?></p>
                     $<?php echo $p['debt_to_players']; ?> 
                        <button class="pay-debt-btn">Pay</button> 
                        <button class="ask-debt-btn">Ask</button>
                    </p> -->
                    <button class="get-out-jail-btn" <?php echo $p['is_in_jail'] ? '' : 'disabled'; ?>>Get Out of Jail</button>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>


    </div>

    <script>
        const currentGameId = <?php echo json_encode($currentGameId); ?>;
    </script>

    <script src="GameFacade.js"></script>
    <script type="module" src="GameActionsProxy.js"></script>
    <script src="GamePage.js"></script>
</body>
</html>