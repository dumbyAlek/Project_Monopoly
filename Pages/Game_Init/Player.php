<?php
// Player.php
class Player {
    private $name;
    private $icon;
    private $order;
    private $money;

    public function __construct(string $name, string $icon, int $order, int $money) {
        $this->name = $name;
        $this->icon = $icon;
        $this->order = $order;
        $this->money = $money;
    }

    public function getName(): string { return $this->name; }
    public function getIcon(): string { return $this->icon; }
    public function getOrder(): int { return $this->order; }
    public function getMoney(): int { return $this->money; }
}
