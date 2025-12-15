<!-- GameConfig.php -->
<?php
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