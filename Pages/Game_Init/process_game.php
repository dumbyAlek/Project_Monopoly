<?php
// process_game.php
require_once __DIR__ . '/../../Database/Database.php';
require_once __DIR__ . '/PlayerFactory.php';
require_once __DIR__ . '/GameConfigBuilder.php';

session_start();
$db = Database::getInstance()->getConnection();
if (!$db) die("DB connection failed.");

// Collect starting values
$startingBankFund = isset($_POST['starting_bank_fund']) ? intval($_POST['starting_bank_fund']) : 15000;
$startingPlayerMoney = isset($_POST['starting_player_money']) ? intval($_POST['starting_player_money']) : 1500;
$passGoMoney = isset($_POST['pass_go_money']) ? intval($_POST['pass_go_money']) : 200;

// Collect player names
$names = [
    isset($_POST['player1']) ? trim($_POST['player1']) : '',
    isset($_POST['player2']) ? trim($_POST['player2']) : '',
    isset($_POST['player3']) ? trim($_POST['player3']) : '',
    isset($_POST['player4']) ? trim($_POST['player4']) : '',
];
for ($i = 0; $i < 4; $i++) {
    if ($names[$i] === '') $names[$i] = 'Player ' . ($i+1);
}

// Icons fixed by slot order
$icons = ["🏰","🚗","🛵","🎩"];

// Build config using Builder + Factory (optional for in-memory)
$builder = new GameConfigBuilder();
$builder->setStartingBankFund($startingBankFund)
        ->setStartingPlayerMoney($startingPlayerMoney)
        ->setPassGoMoney($passGoMoney);

for ($i = 0; $i < 4; $i++) {
    $playerObj = PlayerFactory::create($names[$i], $icons[$i], $i+1, $startingPlayerMoney);
    $builder->addPlayer($playerObj);
}
$config = $builder->build();

// =====================
// Insert Game row
// =====================
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

if ($user_id === null) {
    $stmt = $db->prepare("INSERT INTO Game (user_id, start_time, last_saved_time, passing_GO, status, current_turn, save_file_path)
                          VALUES (NULL, NOW(), NOW(), ?, 'ongoing', 1, NULL)");
    if (!$stmt) die("Prepare failed (Game NULL user): " . $db->error);
    $stmt->bind_param("i", $passGoMoney);
} else {
    $stmt = $db->prepare("INSERT INTO Game (user_id, start_time, last_saved_time, passing_GO, status, current_turn, save_file_path)
                          VALUES (?, NOW(), NOW(), ?, 'ongoing', 1, NULL)");
    if (!$stmt) die("Prepare failed (Game): " . $db->error);
    $stmt->bind_param("ii", $user_id, $passGoMoney);
}

if (!$stmt->execute()) die("Game insert failed: " . $stmt->error);
$game_id = $db->insert_id;
$stmt->close();

// =====================
// Insert Bank row
// =====================
$bst = $db->prepare("INSERT INTO Bank (total_funds, mortgage_rate, interest_rate, backup_status) VALUES (?, 0.00, 0.00, '')");
if (!$bst) die("Prepare failed (Bank): " . $db->error);
$bst->bind_param("i", $startingBankFund);
if (!$bst->execute()) die("Bank insert failed: " . $bst->error);
$bank_id = $bst->insert_id;
$bst->close();

// =====================
// Insert Players and Wallets
// =====================
$insPlayer = $db->prepare("INSERT INTO Player (player_name, money, position, is_in_jail, has_get_out_card, current_game_id) VALUES (?, ?, 0, 0, 0, ?)");
if (!$insPlayer) die("Prepare failed (Player): " . $db->error);

$insWallet = $db->prepare("INSERT INTO Wallet (player_id) VALUES (?)");
if (!$insWallet) die("Prepare failed (Wallet): " . $db->error);

foreach ($names as $i => $pname) {
    $insPlayer->bind_param("sii", $pname, $startingPlayerMoney, $game_id);
    if (!$insPlayer->execute()) die("Player insert failed: " . $insPlayer->error);
    $player_id = $insPlayer->insert_id;

    $insWallet->bind_param("i", $player_id);
    if (!$insWallet->execute()) die("Wallet insert failed: " . $insWallet->error);
}

$insPlayer->close();
$insWallet->close();

// Redirect to game page
header("Location: ../GamePage/GamePage.php?game_id=" . intval($game_id));
exit;
