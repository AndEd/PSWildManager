<?php
namespace pswild;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\item\Item;
use pocketmine\level\generator\Generator;
use pocketmine\level\generator\normal\Normal;

class PSWildManager extends PluginBase implements Listener{
    private $setting = [], $playerData = [];
    public function onEnable(){
        @mkdir($this->getDataFolder());
        @mkdir($this->getServer()->getDataPath() . "/worlds/wild");
        
        $this->setting = $this->load($this->getDataFolder()."setting.json", [
            "wild-level" => ["wild"]
        ]);
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        if(!($this->getServer()->loadLevel("wild"))){
            Generator::addGenerator(Normal::class, "wild");
            $gener = Generator::getGenerator("wild");
            $this->getServer()->generateLevel("wild", 0, $gener, []);
            $this->getLogger()->info("야생 생성 완료.");
        }
        $this->getLogger()->info("야생 로드 완료.");
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
