<?php
// PlayerFactory.php
require_once 'Player.php';

class PlayerFactory {
    public static function create(string $name, string $icon, int $order, int $money): Player {
        return new Player($name, $icon, $order, $money);
    }
}