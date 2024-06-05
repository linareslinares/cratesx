<?php

namespace linareslinares\Form;

use linareslinares\CrateManager;
use linareslinares\Crates;
use linareslinares\Entity\CTEntity;
use linareslinares\utils\FormAPI\CustomForm;
use linareslinares\utils\FormAPI\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class CratesUI {

    private CrateManager $crateManager;

    public function __construct(CrateManager $crateManager) {
        $this->crateManager = $crateManager;
    }

    public function CratesForm(Player $player){
        $form = new SimpleForm(function (Player $player, $data = null){
            if($data === null){
                return true;
            }
            switch($data){
                case 0:
                    $this->getKey($player);
                    break;
                case 1:
                    $this->getKeyAll($player);
                    break;
                case 2:
                    $this->CTCreate($player);
                    break;
                case 3:
                    $this->SpawnCrate($player);
                    break;
                case 4:
                    $this->RemoveCrate($player);
                    break;
            }
        });
        $form->setTitle(TextFormat::colorize("&l&eCRATES&8-&eX"));
        $form->setContent(TextFormat::colorize('&eCrate & Key Manager'));
        $form->addButton(TextFormat::colorize("Get Keys"), 0,"textures/ui/accessibility_glyph_color");
        $form->addButton(TextFormat::colorize("KeyAll"), 0, "textures/ui/accessibility_glyph_color");
        $form->addButton(TextFormat::colorize("Create Crate"), 0, "textures/ui/color_plus");
        $form->addButton(TextFormat::colorize("Set Crate"), 0, "textures/ui/recipe_book_icon");
        $form->addButton(TextFormat::colorize("Remove Crate"), 0, "textures/ui/trash");
        $form->addButton(TextFormat::colorize("Close"), 0, "textures/ui/cancel");
        $form->sendToPlayer($player);
        return $form;
    }

    private function getKey(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $crateName = strval($data[0]);
            $amount = (int)$data[1];

            if (!$this->crateManager->crateExists($crateName)) {
                $player->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&cThe crate '{$crateName}' does not exist."));
                return;
            }

            if ($amount <= 0) {
                $player->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&cAmount must be a positive integer."));
                return;
            }

            for ($i = 0; $i < $amount; $i++) {
                $this->crateManager->createKey($player, $crateName);
            }

            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(str_replace(["{amount}", "{crateName}"], ["{$amount}", "{$crateName}"], Crates::getInstance()->config->get("keyall_msg"))));
        });

        $form->setTitle(TextFormat::colorize("&l&eGET KEY"));
        $form->addInput("Enter the name of the crate", "crateName");
        $form->addInput("Enter the amount of keys", "amount", "1");
        $player->sendForm($form);
    }

    private function getKeyAll(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $crateName = strval($data[0]);
            $amount = (int)$data[1];

            if (!$this->crateManager->crateExists($crateName)) {
                $player->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&cThe crate '{$crateName}' does not exist."));
                return;
            }

            if ($amount <= 0) {
                $player->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&cAmount must be a positive integer."));
                return;
            }

            $players = Crates::getInstance()->getServer()->getOnlinePlayers();
            foreach ($players as $playerall) {
                for ($i = 0; $i < $amount; $i++) {
                    $this->crateManager->createKey($playerall, $crateName);
                }
                $playerall->sendMessage(Crates::getInstance()->prefix.TextFormat::colorize(str_replace(["{amount}", "{crateName}"], ["{$amount}", "{$crateName}"], Crates::getInstance()->config->get("keyall_msg"))));
            }

            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(str_replace(["{amount}", "{crateName}"], ["{$amount}", "{$crateName}"], Crates::getInstance()->config->get("keyallsend"))));
        });

        $form->setTitle(TextFormat::colorize("&l&eKEY ALL"));
        $form->addInput("Enter the name of the crate", "crateName");
        $form->addInput("Enter the amount of keys", "amount", "1");
        $player->sendForm($form);
    }

    private function CTCreate(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $crateName = strval($data[0]);
            $items = $player->getInventory()->getContents();
            $this->crateManager->createCrate($crateName, $items);
            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::YELLOW.  "Crate '$crateName' created with current inventory items.");
        });

        $form->setTitle(TextFormat::colorize("&l&eCRATE CREATOR"));
        $form->addInput("Enter the name of the crate", "crateName");
        $player->sendForm($form);
    }

    private function SpawnCrate(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $crateName = strval($data[0]);
            $entity = new CTEntity($player->getLocation());
            $entity->crateName = $crateName;
            $msg = TextFormat::colorize("&e» Crate {$crateName} «\n&6» Click with {$crateName} key «");
            $entity->setNameTag($msg);
            $entity->spawnToAll();
            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&eCrate {$crateName} spawned."));
        });

        $form->setTitle(TextFormat::colorize("&l&eCRATE SPAWN"));
        $form->addInput("Enter the name of the crate", "crateName");
        $player->sendForm($form);
    }

    private function RemoveCrate(Player $player): void {
        $form = new CustomForm(function (Player $player, ?array $data) {
            if ($data === null) {
                return;
            }

            $crateName = strval($data[0]);
            $this->crateManager->removeCrate($crateName);
            $player->sendMessage(Crates::getInstance()->prefix . TextFormat::colorize("&cCrate {$crateName} removed."));
        });

        $form->setTitle(TextFormat::colorize("&l&eCRATE REMOVE"));
        $form->addInput("Enter the name of the crate", "crateName");
        $player->sendForm($form);
    }
}