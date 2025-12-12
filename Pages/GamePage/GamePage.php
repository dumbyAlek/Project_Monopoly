<!-- GamePage.php -->

<?php
session_start();
require_once __DIR__ . '/../../Database/Database.php';
require_once __DIR__ . '/DataFacade.php'; // include the new DataFacade

$db = Database::getInstance()->getConnection();
$currentGameId = $_GET['game_id'] ?? 1;

$dataFacade = new DataFacade($db, $currentGameId);

// Fetch bank and players using facade
$bank = $dataFacade->getBank();
$players = $dataFacade->getPlayers();
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
    <!-- Left Sidebar: Bank -->
    <aside class="sidebar left-sidebar">

        <div class="bank-panel">
            <img src="../../Assets/counter1.webp" alt="Cash Counter" class="cash-counter">

            <p class="bank-money">Bank Money: $<?php echo $bank['total_funds']; ?></p>
            <p class="bank-mortgages">Mortgages: $0</p>
            <p class="bank-loans">Loans: $0</p>

            <button onclick="GameFacade.saveGame(<?php echo $currentGameId; ?>)">Save Game</button>

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

            <aside class="sidebar right-sidebar">
        <div class="players-container">
            <?php foreach($players as $p): ?>
                <div class="player-panel" data-player-id="<?php echo $p['player_id']; ?>">
                    <!-- <h3><?php echo htmlspecialchars($p['player_name']); ?> <img src="../../Assets/player-icon.png" alt="icon" class="player-icon"></h3> -->
                    <p>Money: $<?php echo $p['money']; ?></p>
                    <p>Properties: <?php echo $p['number_of_properties']; ?> ($<?php echo $p['propertyWorthCash']; ?>)</p>
                    <p>Get Out of Jail Card: <?php echo $p['has_get_out_card'] ? "Yes" : "No"; ?></p>
                    <p>Loan: $0 <button class="pay-loan-btn">Pay</button></p>
                    <p>Debt from Players: $<?php echo $p['debt_from_players']; ?></p>
                    <p>Debt to Players: $<?php echo $p['debt_to_players']; ?> 
                        <button class="pay-debt-btn">Pay</button> 
                        <button class="ask-debt-btn">Ask</button>
                    </p>
                    <button class="get-out-jail-btn" <?php echo $p['is_in_jail'] ? '' : 'disabled'; ?>>Get Out of Jail</button>
                </div>
            <?php endforeach; ?>
        </div>
    </aside>

    </div>

    <script src="GameFacade.js"></script>
    <script src="GameActionsProxy.js"></script>
    <script src="GamePage.js"></script>
</body>
</html>
