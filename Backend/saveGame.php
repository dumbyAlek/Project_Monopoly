<?php
session_start();
require_once __DIR__ . '/../Database/Database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['gameId'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid input: missing gameId']);
    exit;
}

$db = Database::getInstance()->getConnection();
$gameId = (int)$data['gameId'];

$bank = $data['bank'] ?? null;
$players = $data['players'] ?? null;
$properties = $data['properties'] ?? null;

$isFullSave = is_array($bank) && is_array($players);

try {
    $db->begin_transaction();

    // Always touch last_saved_time
    $st = $db->prepare("UPDATE Game SET last_saved_time = NOW() WHERE game_id = ?");
    $st->bind_param("i", $gameId);
    $st->execute();

    // LIGHT SAVE
    if (!$isFullSave) {
        $db->commit();
        echo json_encode(['success' => true, 'mode' => 'light']);
        exit;
    }

    // Bank
    $totalFunds = (int)($bank['totalFunds'] ?? 0);
    $stmtBank = $db->prepare("UPDATE Bank SET total_funds = ? WHERE game_id = ?");
    $stmtBank->bind_param("ii", $totalFunds, $gameId);
    $stmtBank->execute();

    // Players
    $stmtPlayer = $db->prepare("
        UPDATE Player
        SET money = ?, position = ?, is_in_jail = ?, has_get_out_card = ?
        WHERE player_id = ? AND current_game_id = ?
    ");

    // Wallet
    $stmtWallet = $db->prepare("
        UPDATE Wallet
        SET number_of_properties = ?, propertyWorthCash = ?, debt_to_players = ?, debt_from_players = ?
        WHERE player_id = ?
    ");

    foreach ($players as $p) {
        $playerId = (int)($p['player_id'] ?? 0);
        if ($playerId <= 0) continue;

        $money = (int)($p['money'] ?? 0);
        $position = (int)($p['position'] ?? 0);
        $inJail = !empty($p['is_in_jail']) ? 1 : 0;
        $hasCard = !empty($p['has_get_out_card']) ? 1 : 0;

        $stmtPlayer->bind_param(
            "iiiiii",
            $money,
            $position,
            $inJail,
            $hasCard,
            $playerId,
            $gameId
        );

        $stmtPlayer->execute();

        $propCount = (int)($p['number_of_properties'] ?? 0);
        $propWorth = (int)($p['propertyWorthCash'] ?? 0);
        $debtTo = (int)($p['debt_to_players'] ?? 0);
        $debtFrom = (int)($p['debt_from_players'] ?? 0);

        $stmtWallet->bind_param(
            "iiiii",
            $propCount,
            $propWorth,
            $debtTo,
            $debtFrom,
            $playerId
        );

        $stmtWallet->execute();
    }

    // Properties
    if (is_array($properties)) {
        $stmtProp = $db->prepare("
            UPDATE Property
            SET owner_id = ?, house_count = ?, hotel_count = ?, is_mortgaged = ?
            WHERE property_id = ? AND current_game_id = ?
        ");

        foreach ($properties as $pr) {
            $propertyId = (int)($pr['property_id'] ?? 0);
            if ($propertyId <= 0) continue;

            $ownerRaw = $pr['owner_id'] ?? null;
            $ownerId = ($ownerRaw === null || $ownerRaw === "" || (int)$ownerRaw === 0) ? null : (int)$ownerRaw;

            $houseCount = (int)($pr['house_count'] ?? 0);
            $hotelCount = (int)($pr['hotel_count'] ?? 0);
            $isMortgaged = !empty($pr['is_mortgaged']) ? 1 : 0;

            $stmtProp->bind_param("iiiiii", $ownerId, $houseCount, $hotelCount, $isMortgaged, $propertyId, $gameId);
            $stmtProp->execute();
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'mode' => 'full']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
