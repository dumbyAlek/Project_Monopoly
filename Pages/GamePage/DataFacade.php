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
            SELECT 
                p.player_id, 
                p.player_name, 
                p.money, 
                p.position,
                p.is_in_jail, 
                p.has_get_out_card, 
                COALESCE(w.propertyWorthCash, 0)    AS propertyWorthCash,
                COALESCE(w.number_of_properties, 0) AS number_of_properties,
                COALESCE(w.debt_to_players, 0)      AS debt_to_players,
                COALESCE(w.debt_from_players, 0)    AS debt_from_players
            FROM Player p
            LEFT JOIN Wallet w ON p.player_id = w.player_id
            WHERE p.current_game_id = ?
            ORDER BY p.player_id ASC
        ");
        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private array $propertyToTileMap = [
        1  => 1,
        2  => 3,
        3  => 5,
        4  => 6,
        5  => 8, 
        6  => 9,
        7  => 11,
        8  => 12,
        9  => 13,
        10 => 14,
        11 => 15,
        12 => 16,
        13 => 18,
        14 => 19,
        15 => 21,
        16 => 23,
        17 => 24,
        18 => 25,
        19 => 26,
        20 => 27,
        21 => 28,
        22 => 29,
        23 => 31,
        24 => 32,
        25 => 34,
        26 => 35,
        27 => 37,
        28 => 39
    ];

    public function getProperties(): array {
        $stmt = $this->db->prepare("
            SELECT property_id, price, rent, owner_id, house_count, hotel_count, is_mortgaged
            FROM Property
            WHERE current_game_id = ?
            ORDER BY property_id ASC
        ");

        $stmt->bind_param("i", $this->gameId);
        $stmt->execute();

        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);

        $tiles = [];
        $tileOrder = array_values($this->propertyToTileMap);

        foreach ($rows as $i => $prop) {
            if (!isset($tileOrder[$i])) continue;

            $tileIndex = $tileOrder[$i];

            $tiles[$tileIndex] = [
            'id'          => (int)$prop['property_id'],
            'price'       => (int)$prop['price'],
            'rent'        => (int)$prop['rent'],
            'owner_id'    => $prop['owner_id'] !== null ? (int)$prop['owner_id'] : null,
            'house_count' => (int)$prop['house_count'],
            'hotel_count' => (int)$prop['hotel_count'],
            'is_mortgaged'=> (bool)$prop['is_mortgaged'],
            ];

        }

        return $tiles;
    }


}
?>
