<?php
require_once 'GamePlayer.php';
require_once 'GameConfig.php';

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

    public function addPlayer(GamePlayer $p) {
        $this->players[] = $p;
        return $this;
    }

    public function build(): GameConfig {
        return new GameConfig($this->bankFund, $this->playerMoney, $this->players, $this->passGoMoney);
    }
}