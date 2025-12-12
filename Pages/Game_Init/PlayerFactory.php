<?php
// PlayerFactory.php
require_once 'GamePlayer.php';

class PlayerFactory {
    public static function create(string $name, string $icon, int $order, int $money): Player {
        return new GamePlayer($name, $icon, $order, $money);
    }
}