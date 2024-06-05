<?php

namespace linareslinares\Commands;

use linareslinares\CrateManager;
use linareslinares\Crates;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class KeyAllCommand extends Command {

    private CrateManager $crateManager;
    public function __construct(CrateManager $crateManager) {
        parent::__construct("keyall", "Key for Crates-X", null);
        $this->setPermission("keyall.command.use");
        $this->crateManager = $crateManager;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if ($sender instanceof Player) {
            if (empty($args[0])) {
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&eUsage: /keyall [name] [amount]"));
                return;
            }

            $crateName = $args[0];
            $amount = (int)($args[1] ?? 1);

            if (!$this->crateManager->crateExists($crateName)) {
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&cThe crate '{$crateName}' does not exist."));
                return;
            }

            if ($amount <= 0) {
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&cAmount must be a positive integer."));
                return;
            }

            $players = Crates::getInstance()->getServer()->getOnlinePlayers();
            foreach ($players as $player) {
                for ($i = 0; $i < $amount; $i++) {
                    $this->crateManager->createKey($player, $crateName);
                }
                $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(str_replace(["{amount}", "{crateName}"], ["{$amount}", "{$crateName}"], Crates::getInstance()->config->get("keyall_msg"))));
            }

            $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(str_replace(["{amount}", "{crateName}"], ["{$amount}", "{$crateName}"], Crates::getInstance()->config->get("keyallsend"))));
        }
    }
}