<?php
require_once "../../Database/Database.php";
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$playerId = intval($data['playerId']);
$propertyId = intval($data['propertyId']);

$db = Database::getInstance()->getConnection();
$db->begin_transaction();

if (!$data || !isset($data['playerId'], $data['propertyId'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON input'
    ]);
    exit;
}

try {
    // Get player info
    $playerStmt = $db->prepare("SELECT money, current_game_id FROM Player WHERE player_id = ? FOR UPDATE");
    if (!$playerStmt) throw new Exception("Prepare failed (Player): " . $db->error);

    $playerStmt->bind_param("i", $playerId);
    $playerStmt->execute();
    $player = $playerStmt->get_result()->fetch_assoc();
    $playerStmt->close();

    if (!$player) throw new Exception("Player not found");
    $gameId = (int)$player['current_game_id'];

    // Get property info
    $propStmt = $db->prepare("SELECT price, owner_id FROM Property WHERE property_id = ? AND current_game_id = ? FOR UPDATE");
    if (!$propStmt) throw new Exception("Prepare failed (Property): " . $db->error);

    $propStmt->bind_param("ii", $propertyId, $gameId);
    $propStmt->execute();
    $property = $propStmt->get_result()->fetch_assoc();
    $propStmt->close();

    if (!$property) throw new Exception("Property not found");

    if ($property['owner_id'] !== null) {
        throw new Exception("Property already owned");
    }

    $price = (int)$property['price'];
    if ($player['money'] < $price) {
        throw new Exception("Insufficient funds");
    }

    // Get bank for this game
    $bankStmt = $db->prepare("SELECT bank_id, total_funds FROM Bank WHERE game_id = ?");
    if (!$bankStmt) throw new Exception("Prepare failed (Bank): " . $db->error);

    $bankStmt->bind_param("i", $gameId);
    $bankStmt->execute();
    $bank = $bankStmt->get_result()->fetch_assoc();
    $bankStmt->close();

    if (!$bank) throw new Exception("Bank not found");

    // Deduct money from player
    $updatePlayer = $db->prepare(
        "UPDATE Player 
        SET money = money - ? 
        WHERE player_id = ? AND current_game_id = ?"
    );
    $updatePlayer->bind_param("iii", $price, $playerId, $gameId);

    $updatePlayer->execute();
    if ($updatePlayer->affected_rows !== 1) {
        throw new Exception("Failed to update player balance.");
    }

    // Add money to bank
    $bankId = (int)$bank['bank_id'];
    $updateBank = $db->prepare("UPDATE Bank SET total_funds = total_funds + ? WHERE bank_id = ?");
    $updateBank->bind_param("ii", $price, $bankId);
    $updateBank->execute();
    if ($updateBank->affected_rows !== 1) {
        throw new Exception("Failed to update bank balance.");
    }

    // Set property owner
    $updateProp = $db->prepare("UPDATE Property SET owner_id = ? WHERE property_id = ? AND current_game_id = ?");
    $updateProp->bind_param("iii", $playerId, $propertyId, $gameId);
    $updateProp->execute();
    if ($updateProp->affected_rows !== 1) {
        throw new Exception("Failed to assign property (wrong game_id or already updated).");
    }


    // Ensure Wallet row exists (safe)
    $st = $db->prepare("
        INSERT IGNORE INTO Wallet (player_id, propertyWorthCash, number_of_properties, debt_to_players, debt_from_players)
        VALUES (?, 0, 0, 0, 0)
    ");
    $st->bind_param("i", $playerId);
    $st->execute();
    $st->close();

    // Update wallet (bank purchase uses the property's listed price)
    $walletStmt = $db->prepare("
    UPDATE Wallet
    SET propertyWorthCash = propertyWorthCash + ?,
        number_of_properties = number_of_properties + 1
    WHERE player_id = ?
    ");
    $walletStmt->bind_param("ii", $price, $playerId);
    $walletStmt->execute();
    $walletStmt->close();

    // Log bank transaction
    $bankTransStmt = $db->prepare("
        INSERT INTO BankTransaction (bank_id, player_id, property_id, type, amount, timestamp)
        VALUES (?, ?, ?, 'purchase', ?, NOW())
    ");
    $bankTransStmt->bind_param("iiii", $bankId, $playerId, $propertyId, $price);
    $bankTransStmt->execute();

    // Log action
    $desc = "Player $playerId bought property $propertyId from bank";
    $logStmt = $db->prepare("INSERT INTO Log (game_id, description, timestamp) VALUES (?, ?, NOW())");
    $logStmt->bind_param("is", $gameId, $desc);
    $logStmt->execute();

    $db->commit();
    $updatePlayer->close();
    $updateBank->close();
    $updateProp->close();

    $bankTransStmt->close();
    $logStmt->close();

    echo json_encode([
        'success' => true,
        'newBalance' => $player['money'] - $price,
        'owned' => true,
        'message' => 'Property purchased from bank'
    ]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
