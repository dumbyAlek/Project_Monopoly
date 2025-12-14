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

$BOARD_PROPERTIES = [
    // tile_index => property data
    1  => ['price' => 60,  'rent' => 2],
    3  => ['price' => 60,  'rent' => 4],
    5  => ['price' => 200, 'rent' => 25], // railroad
    6  => ['price' => 100, 'rent' => 6],
    8  => ['price' => 100, 'rent' => 6],
    9  => ['price' => 120, 'rent' => 8],
    11 => ['price' => 140, 'rent' => 10],
    12 => ['price' => 150, 'rent' => 10], // utility
    13 => ['price' => 140, 'rent' => 10],
    14 => ['price' => 160, 'rent' => 12],  
    // continue for your board
];

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
    // If not logged in, store NULL user_id
    $stmt = $db->prepare("
        INSERT INTO Game (user_id, start_time, last_saved_time, passing_GO, status)
        VALUES (NULL, NOW(), NOW(), ?, 'ongoing')
    ");
    if (!$stmt) die("Prepare failed (Game): " . $db->error);

    $stmt->bind_param("i", $passGoMoney);

} else {
    // If logged in, store the real user_id
    $stmt = $db->prepare("
        INSERT INTO Game (user_id, start_time, last_saved_time, passing_GO, status)
        VALUES (?, NOW(), NOW(), ?, 'ongoing')
    ");
    if (!$stmt) die("Prepare failed (Game): " . $db->error);

    $stmt->bind_param("ii", $user_id, $passGoMoney);
}

if (!$stmt->execute()) die("Game insert failed: " . $stmt->error);
$game_id = $db->insert_id;
$stmt->close();

// =====================
// Insert Bank row
// =====================
$bst = $db->prepare("INSERT INTO Bank (game_id, total_funds) VALUES (?, ?)");
if (!$bst) die("Prepare failed (Bank): " . $db->error);
$bst->bind_param("ii", $game_id, $startingBankFund);
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

// =====================
// Insert Places
// =====================
$propStmt = $db->prepare("
    INSERT INTO Property (price, rent, house_count, hotel_count, is_mortgaged, owner_id, current_game_id)
    VALUES (?, ?, 0, 0, 0, NULL, ?)
");

if (!$propStmt) die("Prepare failed (Property): " . $db->error);

foreach ($BOARD_PROPERTIES as $tileIndex => $prop) {
    $propStmt->bind_param(
        "iii",
        $prop['price'],
        $prop['rent'],
        $game_id
    );

    if (!$propStmt->execute()) {
        die("Property insert failed: " . $propStmt->error);
    }

    // IMPORTANT: store tile_index → property_id mapping in memory
    $propertyId = $propStmt->insert_id;

    // Optional: keep this if you want to log/debug
    // error_log("Tile $tileIndex => Property $propertyId");
}

$propStmt->close();




$insPlayer->close();
$insWallet->close();

// Redirect to game page
header("Location: ../GamePage/GamePage.php?game_id=" . intval($game_id));
exit;
