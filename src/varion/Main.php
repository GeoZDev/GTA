<?php
/**
 * Created by PhpStorm.
 * User: Varion
 * Date: 15/06/2018
 * Time: 20:39
 * Copyright Varion, don't leak it!
 * THIS IS THE FINAL-BETA VERSION.
 */
namespace varion;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\comman
use pocketmine\nbt\tag\DoubleTag;\player\PlayerCommandPreprocessEvent;
use pocketmine\level\particle\DestroyBlockParticle as BloodParticle;
use pocketmine\level\particle\FlameParticle as WeaponShootParticle;
use pocketmine\level\sound\AnvilFallSound as DropBombSound;
use pocketmine\level\sound\BlazeShootSound as WeaponShootSound;
use pocketmine\level\sound\DoorCrashSound as ExplodeSound;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\Snowball as Bullet;
use pocketmine\entity\Entity;
use pocketmine\entity\Snowball;
use pocketmine\entity\Egg;
use pocketmine\entity\PrimedTNT;
use pocketmine\nbt\tag\CompoundTag;d\CommandExecutor;
use pocketmine\event
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\event\player\PlayerMoveEvent as PlayerWalkEvent;
use pocketmine\event\player\PlayerInteractEvent as PlayerUseWeaponEvent;
use pocketmine\event\player\cheat\PlayerCheatEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\inventory\Inventory;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\TextFormat as C;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use onebone\economyapi\EconomyAPI;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

class Main extends PluginBase implements Listener
{
    public $cmds = ["ban", "ban-ip", "bancid", "bancidbyname", "banipbyname"];

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "lp/");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("§aEnabling GTA plugin");
        $this->bank = new Config($this->getDataFolder() ."bank.yml", Config::YAML, []);
        $this->eco = EconomyAPI::getInstance();
    }

    public function taoNguoiDung($ten){
        $ten = strtolower($ten);
        $this->bank->set($ten,0);
        $this->bank->save();
    }
    public function congTien($ten,$tien){
        $ten = strtolower($ten);
        $tienhienco = $this->bank->get($ten);
        $this->bank->set($ten,$tienhienco + $tien);
        $this->bank->save();
    }
    public function truTien($ten,$tien){
        $ten = strtolower($ten);
        $this->congTien($ten,-$tien);
    }
    public function caiTien($ten,$tien){
        $ten = strtolower($ten);
        $this->bank->set($ten,$tien);
        $this->bank->save();
    }
    public function xemTien($ten){
        $ten = strtolower($ten);
        if($this->kiemTra($ten)){
            $tienhienco = $this->bank->get($ten);
            return $tienhienco;
        }
        return false;
    }
    public function kiemTra( $ten){
        $ten = strtolower($ten);
        if($this->bank->exists($ten)){
            return true;
        }
        return false;
    }

    public function onPreProcess(PlayerCommandPreprocessEvent $ev)
    {
        $p = $ev->getPlayer();
        $m = $ev->getMessage();
        $e = explode(" ", $m);
        if ($m[0] == "/") {
            $c = substr($e[0], 1);
            if (in_array($c, $this->cmds)) {
                foreach ($this->getConfig()->get("protected") as $unb) {
                    if ($unb == $e[1]) {
                        $p->sendMessage(TextFormat::RED . "Non puoi bannare quel player!");
                        $ev->setCancelled(true);
                    }
                }
            }
        }

        public
        function onExplode(ExplosionPrimeEvent $event)
        {
            $event->setBlockBreaking(false);
        }

        public
        function onVoidDamage(EntityDamageEvent $event): void
        {
            $entity = $event->getEntity();
            if ($entity instanceof Player) {
                if ($entity->getPosition()->getY() < 0) {
                    $event->setCancelled(true);
                    $entity->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
                    $event->getPlayer()->sendMessage(TF::AQUA . "[GTA] You have been teleported to the spawn");
                }
            }
        }
    }
	
		public function onPlayerKill(PlayerDeathEvent $event){
		$player = $event->getPlayer();
		$cause = $player->getLastDamageCause();
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$damager->addXp($player->getXpDropAmount());
				$player->setCurrentTotalXp(0);
			}
		}
	}
	
	public function onBreak(BlockBreakEvent $event){
		$event->getPlayer()->addXp($event->getXpDropAmount());
		$event->setXpDrops(0);
	}

    public function getDevice(DataPacketReceiveEvent $event)
    {
        if ($event->getPacket() instanceof LoginPacket) {
            $device = $event->getPacket()->clientData["DeviceOS"];
            $types = ["Unknown", "Android", "iOS", "macOS", "FireOS", "GearVR", "HoloLens", "Windows10", "Windows", "Dedicated", "Orbis", "NX"];
            $this->data[$event->getPacket()->username] = ["OS" => $types[$device]];
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {

        $config = new Config($this->getDataFolder() . "lp/" . strtolower($sender->getName()) . ".yml", Config::YAML);
        switch ($command->getName()) {
            case "level":
                if (!$sender instanceof Player) {
                    $sender->sendMessage(TF::YELLOW . "Please use this Command In-Game!");
                    return true;
                } else {
                    $sender->sendMessage(TF::GRAY . TF::BOLD . "=[ " . TF::RESET . TF::GREEN . "===================" . TF::GRAY . TF::BOLD . " ]=");
                    $sender->sendMessage(" ");
                    $sender->sendMessage(" ");
                    $sender->sendMessage(TF::AQUA . "Your Level: " . TF::GRAY . $config->get("level"));
                    $sender->sendMessage(TF::AQUA . "XP: " . TF::GRAY . $config->get('xp') . "/1000");
                    $sender->sendMessage(" ");
                    $sender->sendMessage(" ");
                    $sender->sendMessage(TF::GRAY . TF::BOLD . "=[ " . TF::RESET . TF::GREEN . "===================" . TF::GRAY . TF::BOLD . " ]=");
                    return false;
                }

            case "hub":
                if ($sender instanceof Player) {
                    $sender->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
                    $sender->setGamemode(0);
                    return false;

                }
                break;

            case "gtahelp":
                if ($sender instanceof Player) {
                    $sender->addActionBarMessage("§l§eGTA HELP LIST");
                    $sender->addTitle("§l§cGTA Help");
                    $sender->sendMessage("§6---- GTA Help ----");
                    $sender->sendMessage("§e/level (visualizza il tuo livello e i tuoi XP)");
                    $sender->sendMessage("§e/gtakit (Ritira il kit Iniziale)");
                    $sender->sendMessage("§e/gta (Visualizza versione del plugin)");
                    $sender->sendMessage("");
                    $sender->sendMessage("§b-=- SEZIONE ARMI -=-");
                    $sender->sendMessage("§cLe armi sono le zappe, le munizioni sono le snowball/egg.");
                    $sender->sendMessage("§cSenza le munizioni non puoi sparare");
                    $sender->sendMessage("§6---- GTA Help ----");
                    return false;
                }
                break;

            case "gta":
                if ($sender instanceof Player) {
                    $sender->addActionBarMessage("§l§aGTA by Varion");
                    $sender->addTitle("§6GTA Plugin");
                    $sender->addSubTitle("§bBy Varion");
                    $sender->sendMessage("§a------GTA------");
                    $sender->sendMessage("§9GTA Plugin by Varion");
                    $sender->sendMessage("§9GTA Version: 2.0-FINAL");
                    $sender->sendMessage("§eUsa /gtahelp per una lista di comandi / aiuti!");
                    $sender->sendMessage("§a------GTA------");
                    return false;
                }
                break;

            case "gtakit":
                if ($sender instanceof Player) {
                    $sender->getInventory()->addItem(Item::get(290, 0, 1));
                    $sender->getInventory()->addItem(Item::get(332, 0, 16));
                    $sender->getInventory()->addItem(Item::get(267, 0, 1));
                    $sender->getInventory()->addItem(Item::get(320, 0, 32));
                    $sender->getInventory()->addItem(Item::get(322, 0, 2));
                    $sender->getInventory()->addItem(Item::get(298, 0, 1));
                    $sender->getInventory()->addItem(Item::get(299, 0, 1));
                    $sender->getInventory()->addItem(Item::get(300, 0, 1));
                    $sender->getInventory()->addItem(Item::get(301, 0, 1));
                    $sender->sendMessage("§a[GTA] §eHai ritirato il kit iniziale.!");
                    return false;

        }
                break;

            case "vipkit":
                if ($sender instanceof Player) {
                    if(!$sender->hasPermission("gta.kitvip")){
                        $sender->sendMessage(TextFormat::RED . "[GTA] Non hai il permesso per usare il kit VIP");
                        return false;
                    }
                    $sender->getInventory()->addItem(Item::get())
                }
                break;

            case "bank":
                if(isset($ar[0])){
                    $ten = $sender->getName();
                    $all = $this->bank->getAll();
                    $money = $this->eco->myMoney($ten);
                    if(!$this->kiemTra($ten)){
                        $this->taoNguoiDung($ten);
                    }
                    if($ar[0] == 'seemoney'){
                        $tienhienco = $this->xemTien($ten);
                        $sender->sendMessage("§fThe amount available in the bank is §a$tienhienco");
                        return true;
                    }
                    if($ar[0] == 'version' or $ar[0] == 'ver'){
                        $sender->sendMessage('§f-> §2Banca GTA§f <-');
                        $sender->sendMessage('Current version : §e1.0');
                        $sender->sendMessage('author : Varion ');
                        return true;
                    }
                    if($ar[0] == 'help'){
                        $sender->sendMessage('§2===Banca GTA==');
                        $sender->sendMessage('/bank sendmoney money (Manda i soldi alla banca)');
                        $sender->sendMessage('/bank takemoney money (ritira i soldi dalla banca)');
                        $sender->sendMessage('/bank transfers money player (Trasferisci soldi dalla banca ad un player)');
                        $sender->sendMessage('/bank seemoney (Guarda quanto hai in banca)');
                        $sender->sendMessage('§2===============');
                        return true;
                    }
                    if(isset($ar[1])){
                        $tien = $ar[1];
                        if(!is_numeric($tien)){
                            $sender->sendMessage('');
                            return false;
                        }
                        $tien = round($tien,3);
                        switch($ar[0]){
                            case "sendmoney":
                                if($money >= $tien){
                                    $this->congTien($ten,$tien);
                                    $this->eco->reduceMoney($ten, $tien);
                                    $sender->sendMessage("§fHai mandato §a$tien §falla banca!");
                                    return true;
                                }
                                $sender->sendMessage("§cPiù soldi invii di quanti ne hai attualmente!");
                                break;
                            case "takemoney":
                                if($this->xemTien($ten) >= $tien){
                                    $this->truTien($ten,$tien);
                                    $this->eco->addMoney($ten,$tien);
                                    $tien = (string)$tien;
                                    $sender->sendMessage("§fHai preso §a$tien §fdalla banca !");
                                    return true;
                                }
                                else
                                    $sender->sendMessage("§cPiù soldi hai prelevato rispetto a quello che hai attualmente!");
                                break;
                            case "transfers":
                                if($this->kiemTra($ten)){
                                    if($this->xemTien($ten) >= strtolower($tien)){
                                        if(isset($ar[2])){
                                            $this->truTien($ten,$tien);
                                            $this->congTien($ar[2],$tien);
                                            foreach($this->getServer()->getOnlinePlayers() as $p){
                                                if(strtolower($ar[2]) == strtolower($p->getName())){
                                                    $nguoinhan = $p;
                                                    break;
                                                }
                                            }
                                            if(isset($nguoinhan)){
                                                $nguoinhan->sendMessage("$ten §fti ha trasferito $ §a$t");
                                                return true;
                                            }
                                            $sender->sendMessage("$ar[2] §fNon è ancora online ma i soldi sono stati trasferiti con successo!");
                                            return true;
                                        }
                                    }
                                    $sender->sendMessage("§eLa quantità di denaro nel tuo account non è sufficiente per effettuare questa transazione!");
                                    return true;
                                }
                                $sender->sendMessage("$ar[2] §cdNon esiste nei dati bancari!");
                                break;
                        }
                    }
                }
                break;
        }
        return false;
        }

    public function onJoin(PlayerJoinEvent $event){
        $config = new Config($this->getDataFolder()."lp/".strtolower($event->getPlayer()->getName()).".yml", Config::YAML);
        $config->save();
        if($config->get("xp") > 0){
        } else {
            $this->getServer()->broadcastMessage(TF::GRAY.TF::BOLD."< ".TF::RESET.TF::GREEN."GTA".TF::BOLD.TF::GRAY." > ".TF::RESET.$event->getPlayer()->getName().TF::GREEN." è entrato nel server per la prima volta!");
            $config->set("xp",10);
            $config->set("level",1);
            $config->save();
            $event->getPlayer()->sendMessage(TF::GRAY.TF::BOLD."=[".TF::RESET.TF::YELLOW."XXXXXXXXXXXXXXXXXXX".TF::BOLD.TF::GRAY."]=");
            $event->getPlayer()->sendMessage(" ");
            $event->getPlayer()->sendMessage(TF::AQUA."Sei livello 1 per essere entrato per la prima volta!");
            $event->getPlayer()->sendMessage(" ");
            $event->getPlayer()->sendMessage(TF::GRAY.TF::BOLD."=[".TF::RESET.TF::YELLOW."XXXXXXXXXXXXXXXXXXX".TF::BOLD.TF::GRAY."]=");
        }
        $event->getPlayer()->setDisplayName(TF::GRAY."[".TF::GREEN."Level: ".TF::YELLOW.$config->get("level").TF::GRAY."] ".$event->getPlayer()->getName());
        $event->getPlayer()->setNameTag("§l§e» ".$event->getPlayer()->getName()."§r\n§l§f» ".$this->data[$event->getPlayer()->getName()]["OS"]);
        $event->getPlayer()->addEffect(new EffectInstance(Effect::getEffectByName("blindness"),200,2));
        $event->getPlayer()->addTitle("§aGTA Server");
        $event->getPlayer()->setHealth(20);
        $event->getPlayer()->setFood(20);
        $event->getPlayer()->sendMessage("§e==============================\n\n      §l§aGTA §7Plugin§r\n     §7Server IP\n\n     §9Welcome back §7".$event->getPlayer()->getName()."\n\n§e==============================");
    }

    public function onMovement(PlayerCheatEvent $event)
    {
        $event->setCancelled();
    }

    public function onMove(PlayerMoveEvent $event){
        $config = new Config($this->getDataFolder()."lp/".strtolower($event->getPlayer()->getName()).".yml", Config::YAML);
        $p = $event->getPlayer();
        $n = $p->getName();
        if($config->get("level") <= 99){ if($config->get("xp") < 1000 ){ $config->set("xp",$config->get("xp")+0.1);
            $config->save();
        } else {
            $config->set("xp",0);
            $config->set("level",$config->get("level") + 1);
            $event->getPlayer()->setDisplayName(TF::GRAY."[".TF::GREEN."Level: ".TF::YELLOW.$config->get("level").TF::GRAY."] ".$event->getPlayer()->getName());
            $this->getServer()->broadcastMessage(TF::GRAY.TF::BOLD."=[ ".TF::RESET.TF::GREEN."GTA".TF::BOLD.TF::GRAY." ]= ".TF::RESET.TF::AQUA."Wow, ".TF::YELLOW.$n.TF::AQUA." è al livello ".TF::YELLOW.$config->get("level"));
            $config->save();
        }
        }
    }

    public function onBlockBreak(BlockBreakEvent $event){
        $config = new Config($this->getDataFolder()."lp/".strtolower($event->getPlayer()->getName()).".yml", Config::YAML);
        $b = $event->getBlock()->getId();
        if($b == 14 or $b == 15 or $b == 16 or $b == 56 or $b == 21){
            if($config->get("level") <= 150){
                $config->set("xp",$config->get("xp")+1);
                $event->getPlayer()->sendTip(TF::AQUA."+1 XP!");
                $config->save();
            }
        }
    }
    public function onDamage(EntityDamageEvent $event)
    {
        if ($event instanceof EntityDamageByChildEntityEvent) {
            $child = $event->getChild();
            if ($child instanceof Snowball) {
                $event->setDamage(2);
                if ($child instanceof Egg) {
                    $event->setDamage(5);
                    if ($child->y - $event->getEntity()->y > 1.35) {
                        $event->setDamage(8);
                        if ($event instanceof EntityDamageByEntityEvent) {
                            if ($event->getDamager() instanceof Player or $event->getDamager() instanceof Snowball or $event->getDamager() instanceof Egg) {
                                if ($event->getEntity() instanceof Player) {
                                    if (!$event->isCancelled()) {
                                        $event->getEntity()->getLevel()->addParticle(new BloodParticle($event->getEntity(), Block::get(152)));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onShoot(PlayerUseWeaponEvent $event)
    {
        $player = $event->getPlayer();
        $level = $player->getLevel();
        $item = $event->getItem();
        $block = $player->getLevel()->getBlock($player->floor()->subtract(0, 1));
        $fdefault = 1.5;
        $nbtdefault = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x), new DoubleTag("", $player->y + $player->getEyeHeight()), new DoubleTag("", $player->z)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
        if ($item->getId() == 290) {
            if ($player->getInventory()->contains(new Bullet(0, 1))) {
                $bullet = Entity::createEntity("Snowball", $level, $nbtdefault, $player);
                $bullet->setMotion($bullet->getMotion()->multiply($fdefault));
                $bullet->spawnToAll();
                $player->getLevel()->addSound(new WeaponShootSound(new Vector3($player->x, $player->y, $player->z, $player->getLevel())));
                $player->getLevel()->addParticle(new WeaponShootParticle(new Vector3($player->x + 0.4, $player->y, $player->z)));
                $player->getInventory()->removeItem(Item::get(ITEM::SNOWBALL, 0, 1));
                $player->getInventory()->sendContents($player);
            }
        } elseif ($item->getId() == 291) {
            if ($player->getInventory()->contains(new Bullet(0, 1))) {
                $nbt1 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x + 1), new DoubleTag("", $player->y + $player->getEyeHeight()), new DoubleTag("", $player->z)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $nbt2 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x - 1), new DoubleTag("", $player->y + $player->getEyeHeight()), new DoubleTag("", $player->z)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $nbt3 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x), new DoubleTag("", $player->y + $player->getEyeHeight()), new DoubleTag("", $player->z + 1)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $nbt4 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x), new DoubleTag("", $player->y + $player->getEyeHeight()), new DoubleTag("", $player->z - 1)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $nbt5 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x), new DoubleTag("", $player->y + $player->getEyeHeight() + 1), new DoubleTag("", $player->z)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $nbt6 = new CompoundTag("", ["Pos" => new ListTag("Pos", [new DoubleTag("", $player->x), new DoubleTag("", $player->y + $player->getEyeHeight() - 1), new DoubleTag("", $player->z)]), "Motion" => new ListTag("Motion", [new DoubleTag("", -\sin($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI)), new DoubleTag("", -\sin($player->pitch / 180 * M_PI)), new DoubleTag("", \cos($player->yaw / 180 * M_PI) * \cos($player->pitch / 180 * M_PI))]), "Rotation" => new ListTag("Rotation", [new FloatTag("", $player->yaw), new FloatTag("", $player->pitch)])]);
                $bullet1 = Entity::createEntity("Snowball", $level, $nbt1, $player);
                $bullet2 = Entity::createEntity("Snowball", $level, $nbt2, $player);
                $bullet3 = Entity::createEntity("Snowball", $level, $nbt3, $player);
                $bullet4 = Entity::createEntity("Snowball", $level, $nbt4, $player);
                $bullet5 = Entity::createEntity("Snowball", $level, $nbt5, $player);
                $bullet6 = Entity::createEntity("Snowball", $level, $nbt6, $player);
                $bullet1->setMotion($bullet1->getMotion()->multiply($fdefault));
                $bullet2->setMotion($bullet2->getMotion()->multiply($fdefault));
                $bullet3->setMotion($bullet3->getMotion()->multiply($fdefault));
                $bullet4->setMotion($bullet4->getMotion()->multiply($fdefault));
                $bullet5->setMotion($bullet5->getMotion()->multiply($fdefault));
                $bullet6->setMotion($bullet6->getMotion()->multiply($fdefault));
                $bullet1->spawnToAll();
                $bullet2->spawnToAll();
                $bullet3->spawnToAll();
                $bullet4->spawnToAll();
                $bullet5->spawnToAll();
                $bullet6->spawnToAll();
                $player->getLevel()->addSound(new WeaponShootSound(new Vector3($player->x, $player->y, $player->z, $player->getLevel())));
                $player->getLevel()->addParticle(new WeaponShootParticle(new Vector3($player->x + 0.4, $player->y, $player->z)));
                $player->getInventory()->removeItem(Item::get(ITEM::SNOWBALL, 0, 1));
                $player->getInventory()->sendContents($player);
            }
        } elseif ($item->getId() == 292) {
            if ($player->getInventory()->contains(new Bullet(0, 1))) {
                $f = 2;
                $bullet = Entity::createEntity("Egg", $level, $nbtdefault, $player);
                $bullet->setMotion($bullet->getMotion()->multiply($f));
                $bullet->spawnToAll();
                $player->getLevel()->addSound(new WeaponShootSound(new Vector3($player->x, $player->y, $player->z, $player->getLevel())));
                $player->getLevel()->addParticle(new WeaponShootParticle(new Vector3($player->x + 0.4, $player->y, $player->z)));
                $player->getInventory()->removeItem(Item::get(ITEM::EGG, 0, 1));
                $player->getInventory()->sendContents($player);
            }
        } elseif ($item->getId() == 293) {
            if ($player->getInventory()->contains(new Bullet(0, 1))) {
                $f = 3;
                $bullet = Entity::createEntity("Snowball", $level, $nbtdefault, $player);
                $bullet->setMotion($bullet->getMotion()->multiply($f));
                $bullet->spawnToAll();
                $player->getLevel()->addSound(new WeaponShootSound(new Vector3($player->x, $player->y, $player->z, $player->getLevel())));
                $player->getLevel()->addParticle(new WeaponShootParticle(new Vector3($player->x + 0.4, $player->y, $player->z)));
                $player->getInventory()->removeItem(Item::get(ITEM::SNOWBALL, 0, 1));
                $player->getInventory()->sendContents($player);
            }
        } elseif ($item->getId() == 359) {
            if ($player->getInventory()->contains(new Bullet(0, 5)) && $player->isOp()) {
                $f = 0.1;
                $tnt = Entity::createEntity("PrimedTNT", $level, $nbtdefault, $player);
                $tnt->setMotion($tnt->getMotion()->multiply($f));
                $tnt->spawnToAll();
                $player->getLevel()->addSound(new DropBombSound(new Vector3($player->x, $player->y, $player->z, $player->getLevel())));
                $player->getLevel()->addParticle(new WeaponShootParticle(new Vector3($player->x + 0.4, $player->y, $player->z)));
                $player->getInventory()->removeItem(Item::get(ITEM::TNT, 0, 1));
                $player->getInventory()->sendContents($player);
            }
            }
        }
}
