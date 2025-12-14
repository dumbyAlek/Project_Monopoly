<?php
require_once __DIR__ . '../Database/Database.php';
require_once __DIR__ . '../Pages/GamePage/DataFacade.php';

$db = Database::getInstance()->getConnection();
$gameId = $_GET['game_id'];

$dataFacade = new DataFacade($db, $gameId);

echo json_encode([
    "bank" => $dataFacade->getBank(),
    "players" => $dataFacade->getPlayers()
]);
