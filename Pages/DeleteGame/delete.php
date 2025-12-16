<?php
require_once "../../Database/Database.php";

interface GameAction {
    public function execute(int $game_id, int $user_id = null);
}

class DeleteGameStrategy implements GameAction {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    public function execute(int $game_id, int $user_id = null) {

        // Optional filter by user
        $userCondition = $user_id ? "AND user_id = ?" : "";

        // 1️⃣ Delete Logs
        $stmt = $this->db->prepare("DELETE FROM Log WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 2️⃣ Delete BankTransactions
        $stmt = $this->db->prepare("
            DELETE bt 
            FROM BankTransaction bt
            JOIN Bank b ON bt.bank_id = b.bank_id
            WHERE b.game_id = ?
        ");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 3️⃣ Delete Banks
        $stmt = $this->db->prepare("DELETE FROM Bank WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 4️⃣ Delete PersonalTransactions
        $stmt = $this->db->prepare("
            DELETE FROM PersonalTransaction 
            WHERE from_player_id IN (SELECT player_id FROM Player WHERE current_game_id = ?)
               OR to_player_id IN (SELECT player_id FROM Player WHERE current_game_id = ?)
        ");
        $stmt->bind_param("ii", $game_id, $game_id);
        $stmt->execute();
        $stmt->close();

        // 5️⃣ Delete Wallets
        $stmt = $this->db->prepare("
            DELETE w FROM Wallet w
            JOIN Player p ON w.player_id = p.player_id
            WHERE p.current_game_id = ?
        ");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 6️⃣ Delete Properties
        $stmt = $this->db->prepare("DELETE FROM Property WHERE current_game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 7️⃣ Delete Players
        $stmt = $this->db->prepare("DELETE FROM Player WHERE current_game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $stmt->close();

        // 8️⃣ Finally, delete Game
        if ($user_id) {
            $stmt = $this->db->prepare("DELETE FROM Game WHERE game_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $game_id, $user_id);
        } else {
            $stmt = $this->db->prepare("DELETE FROM Game WHERE game_id = ?");
            $stmt->bind_param("i", $game_id);
        }
        $stmt->execute();
        $stmt->close();
    }
}

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

    header("Location: ../HomePage/HomePage.php");
    exit;
}
