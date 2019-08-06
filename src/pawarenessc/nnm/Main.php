<?php

namespace pawarenessc\nnm;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;

use pocketmine\Server;
use pocketmine\Player;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\network\mcpe\protocol\PlaySoundPacket;

use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;

use metowa1227\moneysystem\api\core\API;
use metowa1227\moneysystem\event\money\MoneyIncreaseEvent;

class Main extends pluginBase implements Listener{
	
	public function onEnable(){
		$this->getLogger()->info("=========================");
 		$this->getLogger()->info("NNM-Ranksを読み込みました");
 		$this->getLogger()->info("制作者: PawarenessC");
 		$this->getLogger()->info("バージョン:{$this->getDescription()->getVersion()}");
 		$this->getLogger()->info("=========================");
		
		$this->xp = new Config($this->getDataFolder() ."exp.yml", Config::YAML);
		$this->level = new Config($this->getDataFolder() ."level.yml", Config::YAML);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	
	public function onDisable(){
		$this->getLogger()->info("=========================");
 		$this->getLogger()->info("NNM-Ranksを停止しました。");
 		$this->getLogger()->info("製作者: PawarenessC");
 		$this->getLogger()->info("バージョン:{$this->getDescription()->getVersion()}");
 		$this->getLogger()->info("=========================");
	}
	
	/**
 	* @priority MONITOR
	*/
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		
		if(!$this->xp->exists($name)){
			$this->xp->set($name, 0);
		}
		
		if(!$this->level->exists($name)){
			$this->level->set($name, 1);
		}
		
		$lv = $this->getLv($player);
		$nametag = $player->getNameTag();
		$player->setNameTag("§b[§fLv.{$lv}§b]§f{$nametag}");
		
		$displayname = $player->getDisplayName();
		$player->setDisplayName("§b[§fLv.{$lv}§b]§f{$displayname}");
	}
	
	/**
 	* @ignoreCancelled
 	*/
	public function onBreak(BlockBreakEvent $event){
		$this->addXp($event->getPlayer(),1);
	}
	
	/**
 	* @ignoreCancelled
 	*/
	public function onBlockPlace(BlockPlaceEvent $event){
		$this->addXp($event->getPlayer(),1);
	}
	
	public function onIncrease(MoneyIncreaseEvent $event){
		$player = $event->getPlayer();
		$amount = $event->getAmount();
		
		while($amount > 9){
			$this->addXp($player,1);
			$amount = $amount - 10;
		}
	}
	
	public function addXp($player, int $ex){
		$name = $player->getName();
		$this->xp->set($name, $this->xp->get($name)+$ex);
		$lv = $this->getLv($player);
		$need_xp = $this->getNeedXp($lv);
		$now_xp = $this->getXp($player);
		
		//$player->sendPopup("{$this->getXp($player)} / {$this->getNeedXp($this->getLv($player))}");
		
		if($need_xp < $now_xp){
			$now_xp = $now_xp - $need_xp;
			$this->LevelUp($player);
			if($need_xp > $now_xp){ $this->addXp($player,0); }
		}
	}
	
	public function getXp($player){
		return $this->xp->get($player->getName());
	}
	
	public function getLv($player){
		return $this->level->get($player->getName());
	}
	
	public function LevelUp($player,$bool=true){
		$name = $player->getName();
		$this->level->set($name, $this->getLv($player)+1);
		$old_lv = $this->getLv($player)-1;
		$new_lv = $this->getLv($player);
		
		if($bool) {$player->addTitle("§6Level Up","§o§e{$old_lv}§r§f->§o§a{$new_lv}",20,20,20); }
		
		$pk = new PlaySoundPacket;
		$pk->soundName = "random.levelup";
		$pk->x = $player->x;
		$pk->y = $player->y;
		$pk->z = $player->z;
		$pk->volume = 1;
		$pk->pitch = 1;
		$player->sendDataPacket($pk);
		
		$player->sendMessage("§6レベルアップ！");
		$player->sendMessage("Lv.§e{$old_lv} §f-> Lv.§a{$new_lv}");
		$player->sendMessage("次回のレベルアップに必要なEXPは {$this->getNeedXp($new_lv)}exp です。");
	}
	
	public function getNeedXp($lv){
		return $lv*500;
	}
}
