<?php
require_once "../../Database/Database.php";

// Strategy Interface
interface GameAction {
    public function execute(int $game_id, int $user_id = null);
}

// Delete Strategy
class DeleteGameStrategy implements GameAction {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    public function execute(int $game_id, int $user_id = null) {
        if ($user_id) {
            $stmt = $this->db->prepare("DELETE FROM Game WHERE game_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $game_id, $user_id);
        } else {
            $stmt = $this->db->prepare("DELETE FROM Game WHERE game_id = ?");
            $stmt->bind_param("i", $game_id);
        }
        $stmt->execute();
    }
}

// Context
class GameContext {
    private GameAction $strategy;

    public function setStrategy(GameAction $strategy) {
        $this->strategy = $strategy;
    }

    public function executeAction(int $game_id, int $user_id = null) {
        $this->strategy->execute($game_id, $user_id);
    }
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = intval($_POST['game_id']);
    $user_id = $_SESSION['user_id'] ?? null;

    $db = Database::getInstance()->getConnection();

    $deleteStrategy = new DeleteGameStrategy($db);
    $context = new GameContext();
    $context->setStrategy($deleteStrategy);
    $context->executeAction($game_id, $user_id);

    header("Location: load_saved_games.php?deleted=1");
    exit;
}
