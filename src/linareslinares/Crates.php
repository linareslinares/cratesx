<?php

namespace linareslinares;

use linareslinares\Commands\CrateCommand;
use linareslinares\Commands\KeyAllCommand;
use linareslinares\Commands\KeyCommand;
use linareslinares\CrateManager;
use linareslinares\Entity\CTEntity;
use linareslinares\Listener\Events;
use pocketmine\entity\Entity;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\Server;
use pocketmine\world\World;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\InvMenuHandler;

class Crates extends PluginBase {

    use SingletonTrait;

    public Config $config;

    public $cooldowns = [];


    private CrateManager $crateManager;

    public string $prefix = TextFormat::DARK_GRAY. "[". TextFormat::YELLOW. "CRATES". TextFormat::GOLD. "X". TextFormat::DARK_GRAY. "] ". TextFormat::RESET;

    public function onLoad(): void {
        self::setInstance($this);
    }

    public function onEnable() : void {
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->saveResource('ct_texture.png', false);
        $this->saveResource('ctentity.geo.json', false);
        $this->getLogger()->info("Crates cargando...");
        $this->crateManager = new CrateManager($this);
        $this->getServer()->getCommandMap()->register("crate", new CrateCommand($this));
        $this->getServer()->getCommandMap()->register("key", new KeyCommand($this->crateManager));
        $this->getServer()->getCommandMap()->register("keyall", new KeyAllCommand($this->crateManager));
        $this->getServer()->getPluginManager()->registerEvents(new Events($this->crateManager), $this);

        EntityFactory::getInstance()->register(CTEntity::class,function (World $world, CompoundTag $nbt): CTEntity{
            return new CTEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ['CTEntixdty']);

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        //$this->getScheduler()->scheduleRepeatingTask(new CooldownTask($this), 20);

    }

    public function getCrateManager(): CrateManager {
        return $this->crateManager;
    }


    public static function PlaySound(Player $player, string $sound, int $volume, float $pitch){
        $packet = new PlaySoundPacket();
        $packet->x = $player->getPosition()->getX();
        $packet->y = $player->getPosition()->getY();
        $packet->z = $player->getPosition()->getZ();
        $packet->soundName = $sound;
        $packet->volume = $volume;
        $packet->pitch = $pitch;
        $player->getNetworkSession()->sendDataPacket($packet);
    }
}