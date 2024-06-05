<?php

namespace linareslinares;

use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\TreeRoot;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class CrateManager {

    private Crates $plugin;
    private Config $crates;

    public function __construct(Crates $plugin) {
        $this->plugin = $plugin;
        $this->loadCrates();
    }

    private function loadCrates(): void {
        $this->crates = new Config($this->plugin->getDataFolder() . "crates.yml", Config::YAML);
    }

    public function saveCrates(): void {
        $this->crates->save();
    }

    public function createCrate(string $name, array $items): void {
        $contents = [];

        foreach ($items as $item) {
            $contents[] = $this->serializeItem($item);
        }

        $this->crates->set($name, serialize($contents));
        $this->saveCrates();
    }

    public function getCrate(string $name): array {
        if (!$this->crates->exists($name)) {
            var_dump("Crate not found.");
            return [];
        }

        $deserialized = unserialize($this->crates->get($name));
        $items = [];

        foreach ($deserialized as $entry) {
            $item = $this->deserializeItem($entry);
            $items[] = $item;
        }

        return $items;
    }

    public function editCrate(string $name, array $items): void {
        $contents = [];

        foreach ($items as $item) {
            $contents[] = $this->serializeItem($item);
        }

        $this->crates->set($name, serialize($contents));
        $this->saveCrates();
    }

    public function removeCrate(string $name): void {
        $this->crates->remove($name);
        $this->saveCrates();
    }

    public function listCrates(): array {
        return array_keys($this->crates->getAll());
    }

    public function crateExists(string $crateName): bool {
        return $this->crates->exists($crateName);
    }

    public function getRandomItemFromCrate(string $crateName, string $playerName): void {
        $player = $this->plugin->getServer()->getPlayerExact($playerName);

        if (!$player instanceof Player) {
            var_dump("Player Not Found");
            return;
        }

        if (!$this->crates->exists($crateName)) {
            var_dump("Crate not found");
            return;
        }

        $deserialized = unserialize($this->crates->get($crateName));
        $random_index = array_rand($deserialized);
        $item = $this->deserializeItem($deserialized[$random_index]);
        $inventory = $player->getInventory();
        $itemName = $item->getName();

        if (!$inventory->canAddItem($item)) {
            $player->getWorld()->dropItem($player->getPosition()->asVector3(), $item);
            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(str_replace(["userName", "{itemName}"], [$player->getName(), $itemName], Crates::getInstance()->config->get("won_item"))));
            return;
        }

        $webhookEmpty = Crates::getInstance()->config->get("webhook_url");
        if(!empty($webhookEmpty)){
            $msgd = "**======[CRATES-X]======**\n**- Nick:** {$player->getName()}\n**- Won item:** {$itemName}\n**- In the crate:** {$crateName}\n**- Tag:** || @here ||\n**======[CRATES-X]======**";
            $this->sendWebHook($msgd, $player->getName());
        }

        $steps = [
            [
                'actions' => [
                    function(Player $player) use ($item, $itemName, $inventory, $crateName) {
                        $player->sendTitle(TextFormat::colorize("&e» You won&8:\n&a» {$itemName}"));
                        $inventory->addItem($item);
                        Crates::PlaySound($player, "note.bell", 100, 500);

                        $playersServer = $this->plugin->getServer()->getOnlinePlayers();
                        foreach ($playersServer as $playerOnline){
                            $playerOnline->sendTip(TextFormat::colorize(str_replace(["{userName}", "{itemName}", "{crateName}"], [$player->getName(), $itemName, $crateName], Crates::getInstance()->config->get("won_alert"))));
                        }
                    }
                ]
            ],
            [
                'actions' => [
                    function(Player $player) use ($item, $itemName) {
                        $player->sendTitle(TextFormat::colorize("&e1"), "", 5, 20, 5);
                        Crates::PlaySound($player, "note.harp", 100, 500);
                    }
                ]
            ],
            [
                'actions' => [
                    function(Player $player) use ($item, $itemName) {
                        $player->sendTitle(TextFormat::colorize("&g2"), "", 5, 20, 5);
                        Crates::PlaySound($player, "note.harp", 100, 500);
                    }
                ]
            ],
            [
                'actions' => [
                    function(Player $player) use ($item, $itemName) {
                        $player->sendTitle(TextFormat::colorize("&63"), "", 5, 20, 5);
                        Crates::PlaySound($player, "note.harp", 100, 500);
                    }
                ]
            ]
        ];

        $scheduler = Crates::getInstance()->getScheduler();
        $scheduler->scheduleRepeatingTask(new CooldownTask($player, $steps), 20);

    }

    public function serializeItem(Item $item): string {
        $nbt = new BigEndianNbtSerializer();
        $serializedData = $nbt->write(new TreeRoot($item->nbtSerialize()));
    
        return $this->sanitizeString($serializedData);
    }
    
    public function deserializeItem(string $contents): Item {
        $sanitizedData = $this->sanitizeString($contents);
    
        if (!mb_check_encoding($sanitizedData, "UTF-8")) {
            var_dump("Invalid Serialized Item");
            return VanillaItems::AIR();
        }
    
        $nbt = new BigEndianNbtSerializer();
        return Item::nbtDeserialize($nbt->read($sanitizedData)->mustGetCompoundTag());
    }

    private function sanitizeString(string $data): string {
        return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
    }

    public function createKey(Player $player, string $crateName): void {
        $key = VanillaItems::PAPER();
        $key->setNamedTag(
            CompoundTag::create()->setString("crate", $crateName)
        );
        $key->setCustomName(TextFormat::YELLOW. TextFormat::BOLD. "KEY » ". $crateName);
        $key->setLore(["RIGHT-CLICK TO VIEW REWARDS"]);

        $player->getInventory()->addItem($key);
    }

    public function useKey(Player $player): void {
        $inventory = $player->getInventory();
        $hand = $inventory->getItemInHand();
        $nbt = $hand->getNamedTag()->getTag("crate");

        if (is_null($nbt)) {
            var_dump("Invalid Key");
            return;
        }

        $crate = strval($nbt->getValue());
        if (!$this->crateExists($crate)) {
            var_dump("Crate not found");
            return;
        }

        $keys = $inventory->all(VanillaItems::PAPER());
        if (empty($keys)) {
            if (!$hand->getName() == $nbt){
                var_dump("No keys found in inventory");
                return;
            }
        }

        if ($hand->getCount() > 1) {
            $hand->setCount($hand->getCount() - 1);
            $inventory->setItemInHand($hand);
        } else {
            $player->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize(Crates::getInstance()->config->get("no_keys")));
            $inventory->setItemInHand(VanillaItems::AIR());
        }

        $this->getRandomItemFromCrate($crate, $player->getName());
    }

    public function sendWebHook(string $msg, string $player = "nolog") {
        $name = "CRATES LOGS";
        $webhook = Crates::getInstance()->config->get("webhook_url");
        $cleanMsg = $this->cleanMessage($msg);
        $curlopts = [
            "content" => $cleanMsg,
            "username" => $name
        ];

        if($cleanMsg === ""){
            Crates::getInstance()->getLogger()->warning(TextFormat::RED."ERROR: No se pueden enviar mensajes vacios.");
            return;
        }

        Crates::getInstance()->getServer()->getAsyncPool()->submitTask(new utils\DiscordWebhook\SendAsync($player, $webhook, serialize($curlopts)));
    }

    public function cleanMessage(string $msg) : string{
        $banned = Crates::getInstance()->config->getNested("banned_list", []);
        return str_replace($banned,'',$msg);
    }
}