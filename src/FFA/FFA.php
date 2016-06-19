<?php

namespace FFA;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\tile\Sign;
use pocketmine\scheduler\PluginTask;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class FFA extends PluginBase implements Listener {
    
    public $prefix = TextFormat::DARK_GRAY . "[" . TextFormat::BLUE . "FFA" . TextFormat::DARK_GRAY . "] " . TextFormat::WHITE;
    public $level = 0;
    public $arenaname = "";
    public $gametype = "";

    public function onEnable() {
        $this->getLogger()->info("FFA wurde aktiviert!");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "/arenas");
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new FFATask($this), 20);
    }
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        switch (strtolower($command->getName())) {
            case "ffa":
                if($sender instanceof Player) {
                    if($sender->isOp()) {
                        if(strtolower($args[0]) === "neu" && !(isset($args[1])) && !(isset($args[2]))) {
                            $sender->sendMessage($this->prefix."/ffa neu normal/soup arenaname");
                            return true;
                        } elseif(strtolower($args[0]) === "neu" && isset($args[1]) && !(isset($args[2]))) {
                            $sender->sendMessage($this->prefix."/ffa neu normal/soup arenaname");
                            return true;
                        } elseif(strtolower($args[0]) === "neu" && isset($args[1]) && isset($args[2])) {
                            if (file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[2])) {
                                if (!$this->getServer()->getLevelByName($args[1]) instanceof Level) {
                                    $this->getServer()->loadLevel($args[1]);
                                }
                                $this->level = 1;
                                $this->arenaname = $args[2];
                                $this->gametype = $args[1];
                                $sender->sendMessage($this->prefix."Tippe nun ein Schild an!");
                                return true;
                            } else {
                                $sender->sendMessage($this->prefix.TextFormat::BLUE.$args[2]." existiert ".TextFormat::RED."nicht!");
                                return true;
                            }
                        } else {
                            $sender->sendMessage($this->prefix."/ffa neu normal/soup arenaname");
                            return true;
                        }
                    }
                }
            break;
        }
    }
    
    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $tile = $player->getLevel()->getTile($block);
        if($tile instanceof Sign) {
            $signtext = $tile->getText();
            if($player->isOp() && $this->level == 1) {
                $tile->setText($this->prefix, TextFormat::GREEN."BEITRETEN", $this->arenaname, TextFormat::GOLD.$this->gametype);
                $this->arena = new Config($this->getDataFolder() . "/arenas/" . $this->arenaname . ".yml", Config::YAML);
                $this->arena->set("arena", $this->arenaname);
                $this->arena->set("gametype", $this->gametype);
                $this->arena->set("protectionRadius", 8);
                $this->arena->save();
                $this->level = 0;
                $player->sendMessage("Du hast erfolgreich eine neue Arena erstellt!");
                $player->sendMessage("Der Schutz-Radius am Spawn ist 8 Blöcke groß. Du kannst den Radius jederzeit in der ".$this->arenaname.".yml ändern!");
            } elseif($signtext[0] == $this->prefix && $signtext[1] == TextFormat::GREEN."BEITRETEN") {
                if (!$this->getServer()->getLevelByName($signtext[2]) instanceof Level) {
                    $this->getServer()->loadLevel($signtext[2]);
                }
                $level = $this->getServer()->getLevelByName($signtext[2]);
                $this->arena = new Config($this->getDataFolder() . "/arenas/" . $level->getFolderName() . ".yml", Config::YAML);
                $arena = $this->getServer()->getDefaultLevel()->getSafeSpawn();
                $this->getServer()->getDefaultLevel()->loadChunk($arena->getX(), $arena->getZ());
                $player->teleport($arena, 0, 0);
                $player->sendMessage($this->prefix . "Viel Spaß in FFA(".$signtext[3].")!");
                $player->getInventory()->clearAll();
                $player->setGamemode(0);
                $schwert = Item::get(Item::DIAMOND_SWORD, 0, 1);
                $player->getInventory()->addItem($schwert);
                $essen = Item::get(Item::COOKED_BEEF, 0, 10);
                $player->getInventory()->addItem($essen);
                $bogen = Item::get(Item::BOW, 0, 1);
                $player->getInventory()->addItem($bogen);
                $player->getInventory()->setHelmet(Item::get(306));
                $player->getInventory()->setChestplate(Item::get(307));
                $player->getInventory()->setLeggings(Item::get(308));
                $player->getInventory()->setBoots(Item::get(309));
                $player->getInventory()->sendArmorContents($player);
                foreach ($this->getServer()->getOnlinePlayers() as $players) {
                    $player->showPlayer($players);
                }
                $this->arena->save();
            } elseif($signtext[0] == $this->prefix && $signtext[1] == TextFormat::RED."VOLL") {
                $player->sendMessage($this->prefix . "Diese Arena ist leider schon voll!");
            }
        }
    }
    
    public function onDeath(PlayerDeathEvent $event) {
        if($event->getEntity()->getExperienceLevel() == 123) {
            $player = $event->getEntity();
            $event->setDeathMessage("");
            $lastDmg = $player->getLastDamageCause();
            $event->setDrops(array());
            if ($lastDmg instanceof EntityDamageEvent) {
                if ($lastDmg instanceof EntityDamageByEntityEvent) {
                    $killer = $lastDmg->getDamager();
                    if ($killer instanceof Player) {
                        $killerName = $killer->getName();
                        $event->getEntity()->sendMessage($this->prefix."Du wurdest von ".TextFormat::BOLD.TextFormat::BLUE.$killerName.TextFormat::RESET.TextFormat::WHITE." gekillt!");
                    }
                }
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $event) {
        if($event->getPlayer()->getExperienceLevel() == 123) {
            $player = $event->getPlayer();
            $arena = $player->getLevel()->getSafeSpawn();
            $player->teleport($arena, 0, 0);
            $player->getInventory()->clearAll();
            $schwert = Item::get(Item::DIAMOND_SWORD, 0, 1);
            $player->getInventory()->addItem($schwert);
            $essen = Item::get(Item::COOKED_BEEF, 0, 10);
            $player->getInventory()->addItem($essen);
            $bogen = Item::get(Item::BOW, 0, 1);
            $player->getInventory()->addItem($bogen);
            $player->getInventory()->setHelmet(Item::get(306));
            $player->getInventory()->setChestplate(Item::get(307));
            $player->getInventory()->setLeggings(Item::get(308));
            $player->getInventory()->setBoots(Item::get(309));
            $player->getInventory()->sendArmorContents($player);
        }
    }
}

class FFATask extends PluginTask {

    public function __construct($plugin) {
        $this->plugin = $plugin;
        parent::__construct($plugin);
    }
    
    public $prefix = TextFormat::DARK_GRAY . "[" . TextFormat::BLUE . "FFA" . TextFormat::DARK_GRAY . "] " . TextFormat::WHITE;

    public function onRun($tick) {
        $tiles = $this->getOwner()->getServer()->getDefaultLevel()->getTiles();
        foreach ($tiles as $t) {
            if ($t instanceof Sign) {
                $text = $t->getText();
                if ($text[0] == $this->prefix) {
                    if(!$this->getOwner()->getServer()->isLevelLoaded($text[2])) {
                        $this->getOwner()->getServer()->loadLevel($text[2]);
                    }
                    $arena = $this->getOwner()->getServer()->getLevelByName($text[2]);
                    $aop = count($arena->getPlayers());
                    foreach($arena->getPlayers() as $spieler) {
                        $spieler->setExperienceLevel(123);
                    }
                    $this->plugin->getServer()->getLevelByName($text[3])->setTime(0);
                    if ($aop < 24) {
                        $t->setText($this->prefix, TextFormat::GREEN."BEITRETEN", $text[2], $text[3]);
                    } else {
                        $t->setText($this->prefix, TextFormat::RED."VOLL", $text[2], $text[3]);
                    }
                }
            }
        }
    }

}