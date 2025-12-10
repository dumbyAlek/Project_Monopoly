<?php
class GameConfig {
    public $startMoney;
    public $players;

    public function __construct($startMoney, $players) {
        $this->startMoney = $startMoney;
        $this->players = $players;
    }
}