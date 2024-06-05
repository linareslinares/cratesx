<?php

namespace linareslinares;

use pocketmine\scheduler\Task;
use pocketmine\player\Player;

class CooldownTask extends Task {

    private Player $player;
    private array $steps;
    private int $currentIndex;

    public function __construct(Player $player, array $steps) {
        $this->player = $player;
        $this->steps = $steps;
        $this->currentIndex = count($steps) - 1;
    }

    public function onRun(): void {
        if ($this->currentIndex >= 0) {
            $step = $this->steps[$this->currentIndex];

            if (isset($step['actions']) && is_array($step['actions'])) {
                foreach ($step['actions'] as $action) {
                    $action($this->player);
                }
            }

            $this->currentIndex--;
        } else {
            $this->getHandler()->cancel();
        }
    }
}