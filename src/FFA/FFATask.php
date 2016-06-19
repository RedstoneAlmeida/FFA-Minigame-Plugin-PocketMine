<?php

namespace FFA;

use pocketmine\scheduler\PluginTask;

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