<?php
// GameConfigBuilder.php
require_once 'Player.php';

class GameConfig {
    public $startingBankFund;
    public $startingPlayerMoney;
    public $players; // array of Player objects

    public function __construct($bankFund, $playerMoney, $players) {
        $this->startingBankFund = $bankFund;
        $this->startingPlayerMoney = $playerMoney;
        $this->players = $players;
    }
}

class GameConfigBuilder {
    private $bankFund = 100000;
    private $playerMoney = 1500;
    private $players = [];

    public function setStartingBankFund(int $amt) {
        $this->bankFund = $amt;
        return $this;
    }

    public function setStartingPlayerMoney(int $amt) {
        $this->playerMoney = $amt;
        return $this;
    }

    public function addPlayer(Player $p) {
        $this->players[] = $p;
        return $this;
    }

    public function build(): GameConfig {
        return new GameConfig($this->bankFund, $this->playerMoney, $this->players);
    }
}
