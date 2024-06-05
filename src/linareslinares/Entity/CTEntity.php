<?php

namespace linareslinares\Entity;

use linareslinares\Crates;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Human;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\item\ItemTypeIds;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\Position;
use pocketmine\world\particle\LavaParticle;

class CTEntity extends Human {

    /** @var CompoundTag|null */
    protected $namedTag;

    public float $gravity = 0.0;
    public $canCollide = true;
    protected bool $gravityEnabled = false;
    protected $immobile = true;
    public string $crateName;

    public function __construct(Location $location, ?CompoundTag $nbt = null, string $crateName = "")
    {
        $sdata = $this->PNGtoBYTES(Crates::getInstance()->getDataFolder() . "ct_texture.png");
        $gdata = file_get_contents(Crates::getInstance()->getDataFolder() . "ctentity.geo.json");
        $this->setNoDamageTicks();
        $this->crateName = $crateName;
        parent::__construct($location, new Skin("CTEntity", $sdata, "", "geometry.unknown", $gdata), $nbt);
    }

    public function onUpdate(int $currentTick): bool {
        $pos = $this->getPosition();
        $world = $this->getWorld();
        $world->addParticle($pos->add(0, 1, 0), new ExplodeParticle());

        $this->setScale(1);
        $this->setNameTagAlwaysVisible(true);
        $this->setNoDamageTicks();
        return parent::onUpdate($currentTick);
    }

    public function attack(EntityDamageEvent $source): void {
        $source->cancel();


        if (!$source instanceof EntityDamageByEntityEvent) {
            return;
        }

        $damager = $source->getDamager();

        if (!$damager instanceof Player) {
            return;
        }

        $hand = $damager->getInventory()->getItemInHand();

        if ($hand->equals(VanillaItems::GOLDEN_HOE())) {
            if ($damager->hasPermission("remove.crate.npc")) {
                $this->kill();
                $damager->sendMessage(Crates::getInstance()->prefix. TextFormat::colorize("&eYou removed the crate, Use /ct set [name] to set the crate again."));
            }
        }

        $nbt = $hand->getNamedTag()->getTag("crate");
        if (is_null($nbt)) {
            return;
        }

        $crate = strval($nbt->getValue());
        $crateManager = Crates::getInstance()->getCrateManager();
        if (!$crateManager->crateExists($crate)) {
            return;
        }

        $entity = $source->getEntity();
        $pos = $entity->getPosition();
        $world = $entity->getWorld();
        $world->addParticle($pos, new LavaParticle());
        $world->addParticle($pos, new LavaParticle());
        $world->addParticle($pos->add(1, 0, 0), new LavaParticle());
        $world->addParticle($pos->add(0, 1, 0), new LavaParticle());
        $world->addParticle($pos->add(0, 0, 1), new LavaParticle());
        Crates::PlaySound($damager, "firework.twinkle", 100, 500);
        $crateManager->useKey($damager);
    }

    public function PNGtoBYTES($path): string {
        $img = @imagecreatefrompng($path);
        if ($img !== false) {
            $bytes = "";
            for ($y = 0; $y < (int)@getimagesize($path)[1]; $y++) {
                for ($x = 0; $x < (int)@getimagesize($path)[0]; $x++) {
                    $rgba = @imagecolorat($img, $x, $y);
                    $bytes .= chr(($rgba >> 16) & 0xff) . chr(($rgba >> 8) & 0xff) . chr($rgba & 0xff) . chr(((~((int)($rgba >> 24))) << 1) & 0xff);
                }
            }
            @imagedestroy($img);
            return $bytes;
        } else {
            return "";
        }
    }

    public function setNoDamageTicks(): void {
        $this->noDamageTicks = PHP_INT_MAX;
    }

}