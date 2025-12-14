<!-- DataFacade.php -->
 
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
        ");
        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC); // returns array of players
    }

    private array $propertyToTileMap = [
        // Monopoly standard layout (example – adjust to YOUR board)
        1  => 1,   // Mediterranean Ave
        2  => 3,   // Baltic Ave
        3  => 5,   // Reading Railroad
        4  => 6,   // Oriental Ave
        5  => 8,   // Vermont Ave
        6  => 9,   // Connecticut Ave
        7  => 11,  // St. Charles Place
        8  => 12,  // Electric Company
        9  => 13,  // States Ave
        10 => 14,  // Virginia Ave
        // continue...
    ];

    public function getProperties(): array {
        $stmt = $this->db->prepare("
            SELECT property_id, price, rent, owner_id
            FROM Property
            WHERE current_game_id = ?
            ORDER BY property_id ASC
        ");

        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $tiles = [];
        $boardTileIndexes = array_keys($this->propertyToTileMap);

        foreach ($rows as $i => $prop) {
            if (!isset($boardTileIndexes[$i])) continue;

            $tileIndex = $boardTileIndexes[$i];

            $tiles[$tileIndex] = [
                'id'       => (int)$prop['property_id'],
                'price'    => (int)$prop['price'],
                'rent'     => (int)$prop['rent'],
                'owner_id' => $prop['owner_id']
            ];
        }

        return $tiles;
    }


}
?>
