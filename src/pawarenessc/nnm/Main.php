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
	
	private static $instance;
	
	public static function getInstance(){
		return self::$instance;
	}
	
	public function onLoad(){
		self::$instance = $this;
	}
	
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
		$this->xp->save();
		$this->level->save();
	}
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$name = $sender->getName();
		switch($label){
			case "myxp":
			if(!isset($args[0])){
			$sender->sendMessage("§a{$name} §eLv.{$this->getLv($sender)}".
					     "EXP: §b{$this->getXp($player)} §f/ §d{$this->getNeedXp($this->getLv($player))}".
					     "".
					     "/myxp r でレベルのランキングが見れます");
			break;
			}elseif($args[0] == "r"){
			form = [
			"type" => "form",
			"title" => "§7ランキング",
			"content" => "=======§aレベル§6ランキング§f=======",
			"buttons" => array(array("text" => "戻る","image" => array("type" => "path","data" => "")); ,
			];
			$count = 1; // PJZ9nさんのコード使わせてもらいました、ありがとうございます！
			$all_data = $this->level->getAll();
			arsort($all_data);
			foreach ($all_data as $key => $value){
				$color = "§l§f";
				switch($count){
				case 1:
				$color = "§l§e";
				break;
				case 2:
				$color = "§l§7";
				break;
				case 3:
				$color = "§l§6";
				break;
				}
				if($key == $name){
					$form["content"] .= "\n{$color}{$count}§r. §l§a{$key}§r: Lv.§b{$value}";
					$count++;
				}else{
					$form["content"] .= "\n{$color}{$count}§r. §l§f{$key}§r: Lv.§b{$value}";
					$count++;
				}
			$this->createWindow($player, $form, 472739);
			break;
			}else{
			$sender->sendMessage("ステータスを表示するなら /myxp".
					     "レベルランキングを表示するなら /myxp r");
			break;
			}
			return true;
	}
	
	/**
 	* @priority MONITOR
	*/
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		
		if(!$this->xp->exists($name)){
			$this->xp->set($name, 0);
			$this->xp->save();
			//$player->sendMessage("アカウントが存在しなかったので作成しました xp");
		}
		
		if(!$this->level->exists($name)){
			$this->level->set($name, 1);
			$this->level->save();
			//$player->sendMessage("アカウントが存在しなかったので作成しました Level");
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
		
		if($amount > 1000000){
			$amount = $amount / 10;
			$amount = floor($amount);
			$this->addXp($player,$amount);
		}else{
			
		
		while($amount > 9){
			$this->addXp($player,1);
			$amount = $amount - 10;
		}
		}
	}
	
	public function addXp($player, int $ex){
		$name = $player->getName();
		$this->xp->set($name, $this->xp->get($name)+$ex);
		$this->xp->save();
		$lv = $this->getLv($player);
		$need_xp = $this->getNeedXp($lv);
		$now_xp = $this->getXp($player);
		
		$player->sendPopup("{$this->getXp($player)} / {$this->getNeedXp($this->getLv($player))}");
		
		if($need_xp < $now_xp){
			$now_xp = $now_xp / 500;
			$now_xp = floor($now_xp);
			$this->LevelUp($player,$now_xp);
		}
	}
	
	public function getXp($player){
		return $this->xp->get($player->getName());
	}
	
	public function getLv($player){
		return $this->level->get($player->getName());
	}
	
	public function LevelUp($player,$up=1,$bool=true){
		$name = $player->getName();
		$this->level->set($name, $this->getLv($player)+$up);
		$this->level->save();
		$this->xp->set($name, 0);
		$this->xp->save();
		$old_lv = $this->getLv($player)-$up;
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
					   
	public function createWindow(Player $player, $data, int $id){
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE);
		$player->dataPacket($pk);
	}
	
	/*public function gNNMForm($player){}*/
	/*public function aNNMForm($player){}*/
}
