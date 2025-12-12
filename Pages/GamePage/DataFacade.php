<?php
class DataFacade {
    private $db;
    private $gameId;

    public function __construct($dbConnection, $gameId) {
        $this->db = $dbConnection;
        $this->gameId = $gameId;
    }

    public function getBank() {
        $stmt = $this->db->prepare("SELECT * FROM Bank WHERE game_id = ?");
        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // returns associative array of bank
    }

    public function getPlayers() {
        $stmt = $this->db->prepare("
            SELECT p.player_id, p.player_name, p.money, p.is_in_jail, p.has_get_out_card, 
                   w.propertyWorthCash, w.number_of_properties, w.debt_to_players, w.debt_from_players
            FROM Player p
            LEFT JOIN Wallet w ON p.player_id = w.player_id
            WHERE p.current_game_id = ?
            ORDER BY p.player_id ASC
            LIMIT 4
        ");
        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC); // returns array of players
    }
}
?>
