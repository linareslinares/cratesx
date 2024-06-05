<?php

namespace linareslinares\Listener;

use linareslinares\CrateManager;
use linareslinares\Crates;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\inventory\Inventory;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class Events implements Listener
{

    private CrateManager $crateManager;

    public function __construct(CrateManager $crateManager) {
        $this->crateManager = $crateManager;
    }

    public function Interact(PlayerInteractEvent $event): void {
        $player = $event->getPlayer();
        $inventory = $player->getInventory();
        $hand = $inventory->getItemInHand();

        if (!$player instanceof Player) {
            return;
        }

        if (!$event->getAction() == $event::RIGHT_CLICK_BLOCK) {
            return;
        }

        $nbt = $hand->getNamedTag()->getTag("crate");
        if (is_null($nbt)) {
            return;
        }

        $crate = strval($nbt->getValue());
        if (!$this->crateManager->crateExists($crate)) {
            var_dump("Crate not found");
            return;
        }

        $keys = $inventory->all(VanillaItems::PAPER());
        if (empty($keys)) {
            var_dump("No keys found in inventory");
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_CHEST);
        $menu->setName(TextFormat::colorize("&b&lRewards"));

        $items = $this->crateManager->getCrate($crate);

        $inventoryMenu = $menu->getInventory();
        $inventoryMenu->setContents($items);

        $menu->setListener(function (InvMenuTransaction $transaction): InvMenuTransactionResult {
            $player = $transaction->getPlayer();
            $item = $transaction->getItemClicked();
            return $transaction->discard();
        });

        $menu->setInventoryCloseListener(function(Player $player, Inventory $inventory) {
            Crates::PlaySound($player, "note.bass", 100, 500);
        });

        $menu->send($player);
        Crates::PlaySound($player, "note.bell", 100, 500);

    }

}