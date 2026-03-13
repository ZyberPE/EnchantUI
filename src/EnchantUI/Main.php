<?php

namespace EnchantUI;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\player\Player;

use pocketmine\block\VanillaBlocks;

use pocketmine\item\Sword;
use pocketmine\item\Pickaxe;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Shovel;

use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\enchantment\EnchantmentInstance;

use jojoe77777\FormAPI\SimpleForm;

class Main extends PluginBase implements Listener{

    public function onEnable() : void{
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
    }

    public function onInteract(PlayerInteractEvent $event) : void{

        $block = $event->getBlock();

        if($block->getTypeId() !== VanillaBlocks::ENCHANTING_TABLE()->getTypeId()){
            return;
        }

        $player = $event->getPlayer();
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["hold-item"]);
            return;
        }

        $type = $this->getItemType($item);

        if($type === null){
            $player->sendMessage($this->getConfig()->get("messages")["invalid-item"]);
            return;
        }

        $itemsConfig = $this->getConfig()->get("items");

        if(!isset($itemsConfig[$type])){
            $player->sendMessage($this->getConfig()->get("messages")["invalid-item"]);
            return;
        }

        $this->openEnchantMenu($player,$type);
    }

    private function getItemType($item) : ?string{

        return match(true){

            $item instanceof Sword => "swords",
            $item instanceof Pickaxe => "pickaxes",
            $item instanceof Axe => "axes",
            $item instanceof Shovel => "shovels",
            $item instanceof Armor => "armor",

            default => null
        };
    }

    private function openEnchantMenu(Player $player,string $type) : void{

        $itemsConfig = $this->getConfig()->get("items");
        $enchants = $itemsConfig[$type]["enchants"];

        $form = new SimpleForm(function(Player $player,$data) use ($enchants,$type){

            if($data === null) return;

            $names = array_keys($enchants);
            $selected = $names[$data];

            $this->openLevelMenu($player,$type,$selected);
        });

        $form->setTitle("EnchantUI");
        $form->setContent("Select an enchantment");

        foreach($enchants as $name => $levels){
            $form->addButton(ucfirst(str_replace("_"," ",$name)));
        }

        $player->sendForm($form);
    }

    private function openLevelMenu(Player $player,string $type,string $enchant) : void{

        $levels = $this->getConfig()->get("items")[$type]["enchants"][$enchant];

        $form = new SimpleForm(function(Player $player,$data) use ($levels,$enchant){

            if($data === null) return;

            $levelList = array_keys($levels);

            $level = $levelList[$data];
            $cost = $levels[$level];

            if($player->getXpManager()->getXpLevel() < $cost){
                $player->sendMessage($this->getConfig()->get("messages")["not-enough-xp"]);
                return;
            }

            $item = $player->getInventory()->getItemInHand();

            $enchantObj = $this->getEnchantByName($enchant);

            if($enchantObj === null){
                return;
            }

            $item->addEnchantment(new EnchantmentInstance($enchantObj,$level));

            $player->getInventory()->setItemInHand($item);

            $player->getXpManager()->setXpLevel(
                $player->getXpManager()->getXpLevel() - $cost
            );

            $player->sendMessage($this->getConfig()->get("messages")["success"]);
        });

        $form->setTitle("Enchant Level");

        foreach($levels as $level => $cost){
            $form->addButton("Level $level - $cost XP");
        }

        $player->sendForm($form);
    }

    private function getEnchantByName(string $name){

        return match($name){

            "sharpness" => VanillaEnchantments::SHARPNESS(),
            "smite" => VanillaEnchantments::SMITE(),
            "efficiency" => VanillaEnchantments::EFFICIENCY(),
            "fortune" => VanillaEnchantments::FORTUNE(),
            "unbreaking" => VanillaEnchantments::UNBREAKING(),
            "fire_aspect" => VanillaEnchantments::FIRE_ASPECT(),
            "looting" => VanillaEnchantments::LOOTING(),
            "knockback" => VanillaEnchantments::KNOCKBACK(),

            "protection" => VanillaEnchantments::PROTECTION(),
            "fire_protection" => VanillaEnchantments::FIRE_PROTECTION(),
            "blast_protection" => VanillaEnchantments::BLAST_PROTECTION(),
            "projectile_protection" => VanillaEnchantments::PROJECTILE_PROTECTION(),
            "thorns" => VanillaEnchantments::THORNS(),
            "respiration" => VanillaEnchantments::RESPIRATION(),
            "aqua_affinity" => VanillaEnchantments::AQUA_AFFINITY(),

            "silk_touch" => VanillaEnchantments::SILK_TOUCH(),
            "mending" => VanillaEnchantments::MENDING(),

            default => null
        };
    }
}
