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
    private $normal = [];
    private $wild = [];
    private $wildlast = [];
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);

        Generator::addGenerator(Normal::class, "wild");
        $gener = Generator::getGenerator("wild");
        if(!($this->getServer()->loadLevel("wild"))){
            @mkdir($this->getServer()->getDataPath() . "/" . "worlds" . "/" . "wild");
            $options = [];
            $this->getServer()->generateLevel("wild", 0, $gener, $options);
            $this->getLogger()->info("야생 생성 완료.");
        }
        $this->getLogger()->info("야생 로드 완료.");
    }
    public function onCommand(CommandSender $pl, Command $command, string $label, array $args) : bool{
        if($command->getName() !== "야생") return true;
        if(! $pl instanceof Player) return true;
        if($pl->getLevel()->getName() == "wild"){
        if(! isset($this->normal[$pl->getName()])){$this->normal[$pl->getName()] = [];
        }
        for ($i = 1; $i >= $pl->getInventory()->getSize(); $i++) {
            $item = $pl->getInventory()->getItem($i);
            if($item->getId() == 0) continue;
            $this->wild[$pl->getName()] [$item->getId()] = (! isset($this->wild[$pl->getName()] [$item->getId()]))? 'id => '.$item->getId().','.'dam => '.$item->getDamage().','.'count => '.$this->wild[$pl->getName()] [$item->getId()] + $item->count : 'id => '.$item->getId().','.'dam => '.$item->getDamage().','.'count => '.$item->count;
        }
        $pl->getInventory()->clearAll();
        for ($i = 0; $i >= count($this->normal[$pl->getName()]); $i++) {
            $pl->getInventory()->addItem(new Item($this->normal[$pl->getName()] [$i] ['id'], $this->normal[$pl->getName()] [$i]['dam'],$this->normal[$pl->getName()] [$i]['count']));
        }
        }else{if(! isset($this->wild[$pl->getName()])){$this->wild[$pl->getName()] = [];}
        for ($i = 1; $i >= $pl->getInventory()->getSize(); $i++) {
            $item = $pl->getInventory()->getItem($i);
            if($item->getId() == 0) continue;
            $this->normal[$pl->getName()] [$i - 1] = (! isset($this->normal[$pl->getName()] [$item->getId()]))? 'id => '.$item->getId().','.'dam => '.$item->getDamage().','.'count => '.$this->normal[$pl->getName()] [$item->getId()] + $item->count : 'id => '.$item->getId().','.'dam => '.$item->getDamage().','.'count => '.$item->count;
        }
        $pl->getInventory()->clearAll();
        for ($i = 0; $i >= count($this->wild[$pl->getName()]); $i++) {
            $pl->getInventory()->addItem(new Item($this->wild[$pl->getName()] [$i] ['id'], $this->wild[$pl->getName()] [$i]['dam'],$this->wild[$pl->getName()] [$i]['count']));
        }
        }
        $blname = ($pl->getLevel()->getName() == "wild")? "야생" : "서버";
        $pl->sendMessage($blname."에서의 인벤이 백업이 되었으며 돌아올시에 다시 인벤이 복구 됩니다! 안심하고 플레이 하세요!");
        $blname = ($pl->getLevel()->getName() == "wild")? "world" : "wild";
        if($blname == "wild"){
            $a = (isset($this->wildlast[$pl->getName()]))? new Vector3($this->wildlast[$pl->getName()][0], $this->wildlast[$pl->getName()][1], $this->wildlast[$pl->getName()][2]) : new Vector3(rand(-500, 500),rand(-500, 500),rand(-500, 500));
        $pl->teleport($this->getServer()->getLevelByName($blname)->getSafeSpawn($a),0,0);
        }else{
            $this->wildlast[$pl->getName()] = [$pl->x, $pl->y, $pl->z];
        }
        return true;
    }
}
