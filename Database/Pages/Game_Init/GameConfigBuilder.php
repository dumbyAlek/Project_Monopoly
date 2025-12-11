<?php
// GameConfigBuilder.php
require_once 'Player.php';

class GameConfig {
    public $startingBankFund;
    public $startingPlayerMoney;
    public $players;
    public $passGoMoney; // add this

    public function __construct($bankFund, $playerMoney, $players, $passGoMoney) {
        $this->startingBankFund = $bankFund;
        $this->startingPlayerMoney = $playerMoney;
        $this->players = $players;
        $this->passGoMoney = $passGoMoney;
    }
}

class GameConfigBuilder {
    private $bankFund = 100000;
    private $playerMoney = 1500;
    private $players = [];
    private $passGoMoney = 200; // add default

    public function setPassGoMoney(int $amt) {
        $this->passGoMoney = $amt;
        return $this;
    }

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
        return new GameConfig($this->bankFund, $this->playerMoney, $this->players, $this->passGoMoney);
    }
}