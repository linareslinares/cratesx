<?php

namespace linareslinares\Commands;

use linareslinares\Crates;
use linareslinares\Entity\CTEntity;
use linareslinares\Form\CratesUI;
use pocketmine\block\VanillaBlocks;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CrateCommand extends Command
{

    private $plugin;
    private $cratesUI;

    public function __construct(Crates $plugin) {
        parent::__construct("crate", "Commands for crate management", "/crate <create|edit|set|remove|list> [name]", ["ct"]);
        $this->setPermission("crate.command.use");
        $this->plugin = $plugin;
        $this->cratesUI = new CratesUI($plugin->getCrateManager());
    }

    public function execute(CommandSender $sender, string $label, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "This command can only be used in-game.");
            return;
        }

        if (!isset($args[0])) {
            $this->cratesUI->CratesForm($sender);
            return;
        }

        $crateManager = $this->plugin->getCrateManager();

        switch (strtolower($args[0])) {
            case "create":
                if (!isset($args[1])) {
                    $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Usage: /crate create [name]");
                    return;
                }

                $name = strval($args[1]);
                $items = $sender->getInventory()->getContents();
                $crateManager->createCrate($name, $items);
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW.  "Crate '$name' created with current inventory items.");
                break;

            case "edit":
                if (!isset($args[1])) {
                    $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Usage: /crate edit [name]");
                    return;
                }

                $name = strval($args[1]);
                $items = $sender->getInventory()->getContents();
                $crateManager->editCrate($name, $items);
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Crate '$name' loot updated.");
                break;

            case "set":
                if (!isset($args[1])) {
                    $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Usage: /crate set [name]");
                    return;
                }

                $name = strval($args[1]);
                $entity = new CTEntity($sender->getLocation());
                $entity->crateName = $name;
                $msg = TextFormat::colorize("&e» Crate {$name} «\n&6» Click with {$name} key «");
                $entity->setNameTag($msg);
                $entity->spawnToAll();
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&eCrate {$name} spawned."));
                break;

            case "remove":
                if (!isset($args[1])) {
                    $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Usage: /crate remove [name]");
                    return;
                }

                $name = strval($args[1]);
                $crateManager->removeCrate($name);
                $sender->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&eremoved crate {$name}"));
                break;

            case "list":
                $crates = $crateManager->listCrates();
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "» CRATES LIST\n" . implode("\n", $crates));
                break;

            default:
                $sender->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW. "Unknown command. Usage: /crate <create|edit|set|remove|list> [name]");
                break;
        }
    }
}