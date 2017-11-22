<?php
namespace pswild;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\normal\Normal;
use pocketmine\event\entity\EntityTeleportEvent;

class PSWildManager extends PluginBase implements Listener{
    private $setting = [], $playerData = [];
    public function onEnable(){
        @mkdir($this->getDataFolder());
        @mkdir($this->getServer()->getDataPath() . "/worlds/wild");
        
        $this->loadAll();
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        if(!($this->getServer()->loadLevel("wild"))){
            Generator::addGenerator(Normal::class, "wild");
            $gener = Generator::getGenerator("wild");
            $this->getServer()->generateLevel("wild", 0, $gener, []);
            $this->getLogger()->info("야생 생성 완료.");
        }
        $this->getLogger()->info("야생 로드 완료.");
    }
    
    public function onDisable() {
        $this->saveAll();
    }
    
    public function onTeleport(EntityTeleportEvent $ev) {
        if (!$ev->getEntity() instanceof Player) return;
        if ($ev->isCancelled()) return;
        
        $last = $ev->getFrom();
        $now = $ev->getTo();
        if ($last->getLevel()->getFolderName() !== $now->getLevel()->getFolderName()) return;
        $this->checkInventory($ev->getEntity(), $last, $now);
    }
    
    public function checkInventory(Player $player, Position $last, Position $now) {
        $lastWild = $this->isWild($last->getLevel());
        $nowWild = $this->isWild($now->getLevel());
        $name = strtolower($player->getName());
        if ($nowWild !== $lastWild) {
            if ($nowWild) {
                //move Wild!
                $this->playerData["etc-inven"][$name] = $this->getInventoryData($player);
                $this->setInventoryData($player, $this->playerData["wild-inven"][$name]);
                $this->saveAll(true);
                $player->sendMessage("[!] 야생으로 이동하여 인벤토리가 변경되었습니다!");
            } else {
                //move etc!
                $this->playerData["wild-inven"][$name] = $this->getInventoryData($player);
                $this->setInventoryData($player, $this->playerData["etc-inven"][$name]);
                $this->saveAll(true);
                $player->sendMessage("[!] 메인으로 이동하여 인벤토리가 변경되었습니다!");
            }
        } else {
            //nothing
        }
    }
    
    //TODO: 인벤토리가 변경되는 순간 복사가능성이 있는지 확인하고 있을경우 해당부분 차단구현필요
    
    public function isWild($folderName) {
        if ($folderName instanceof Level) $folderName = $folderName->getFolderName();
        foreach($this->setting["wild-level"] as $name) {
            if ($name == $folderName) return true;
        }
        return false;
    }
    
    public function loadAll() {
        $this->setting = $this->load($this->getDataFolder()."setting.json", [
            "wild-level" => ["wild"]
        ]);
        $this->playerData = $this->load($this->getDataFolder()."user.json", [
            "etc-inven" => [],
            "wild-inven" => []
        ]);
    }
    
    public function saveAll($onlyInven = false) {
        if (!$onlyInven) {
            $this->save($this->getDataFolder()."setting.json", $this->setting, true);
        }
        $this->save($this->getDataFolder()."user.json", $this->playerData, true);
    }
    
    public function load($path, array $defaultArray) {
        return (new Config($path, Config::JSON, $defaultArray))->getAll();
    }
    
    public function save($path, array $data, $async = true) {
        $conf = new Config($path, Config::JSON, []);
        $conf->setAll($data);
        $conf->save($async);
    }
    
    public function setInventoryData(Player $player, array $data) {
        $player->getInventory()->clearAll();
        foreach($i = 0; $i < $size; $i ++) {
            $item->setItem($i, $this->toItem($arr["inven"][$i]));
        }
        foreach($i = 0; $i < 4; $i ++) {
            $now = $i - $size;
            $item->setArmorItem($now, $this->toItem($arr["armor"][$now]));
        }
    }
    
    public function getInventoryData(Player $player) {
        $inventory = $player->getInventory();
        $size = $inventory->getSize();
        $arr = [];
        foreach($i = 0; $i < $size; $i ++) {
            $arr["inven"][$i] = $this->toData($inventory->getItem($i));
        }
        foreach($i = 0; $i < 4; $i ++) {
            $arr["armor"][$i - $size] = $this->toData($inventory->getArmorItem($i));
        }
        return $arr;
    }
    
    public function toData(Item $item) {
        $arr = [];
        if ($item->hasCustomName()) $arr["name"] = $item->getCustomName();
        if (!empty($item->getLore())) $arr["lore"] = $item->getLore();
        if ($item->hasEnchantments()) {
            foreach($item->getEnchantments() as $ench) {
                $arr["enchantment"][$ench->getId()] = $ench->getLevel();
            }
        }
        $arr["id"] = $item->getId();
        $arr["meta"] = $item->getDamage();
        $arr["count"] = $item->getCount();
        return $arr;
    }
    
    public function toItem(array $data) {
        $item = Item::get($data["id"], $data["meta"], $data["count"]);
        if (isset($data["name"])) $item->setCustomName($data["name"]);
        if (isset($data["lore"])) $item->setLore($data["lore"]);
        if (isset($data["enchantment"])) {
            foreach($data["enchantment"] as $id=>$level) {
                $item->addEnchantment(Enchantment::getEnchantment($id)->setLevel($level));
            }
        }
        return $item;
    }
}
