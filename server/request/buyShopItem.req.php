<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Cls\Utils;
use Cls\Utils\Item;
use Schema\Character;

class buyShopItem{
    
    public function __request($player){
        //INDEX = getField('shop_index', FIELD_NUM);
        $target_slot = getField('target_slot', FIELD_NUM);
        $shop_id = getField('item_id', FIELD_NUM);
        if(!$target_slot || !$shop_id)
        	return;
        
        $shop_item = $player->getItemById($shop_id);
        $shopslot = $player->inventory->getSlotByItemId($shop_id);
        if(!$shop_item)
            return Core::setError('errInvItem');
        
        if($shop_item->premium_item && $player->getPremium() < $shop_item->buy_price)
			return Core::setError("errRemovePremiumCurrencyNotEnough");
		if(!$shop_item->premium_item && $player->getMoney() < $shop_item->buy_price)
			return Core::setError("errRemoveGameCurrencyNotEnough");
        
        if($target_slot >= 1 && $target_slot <= 8) //Set
			$target_slotname = Item::$TYPE[$shop_item->type]."_item_id";
		else if($target_slot >= 9 && $target_slot <= 26) //Plecak
			$target_slotname = "bag_item".($target_slot-8)."_id";
		else if($target_slot >= 101 && $target_slot <= 104) //Pasek na amunicje
			$target_slotname = "missiles".($target_slot-100)."_item_id";
		else
			return Core::setError('errInvSlot');
		
		$target_item = $player->getItemFromSlot($target_slotname);
		$replaceData = false;
		if($target_item != null){
			$free_slot = $player->findEmptyInventorySlot();
			if($free_slot == null)
				return Core::setError('errInventoryNoEmptySlot');
			$player->setItemInInventory($target_item, $free_slot);
			$replaceData[] = $free_slot;
			$replaceData[] = $target_item->id;
		}
		    //$target_slotname = $player->findEmptyInventorySlot();
		    
		if(!$player->getTutorialFlag('first_item')){
		    if(Character::count() > 10)
		        $player->setTutorialFlag('first_item', true);
            else{
                $player->character->tutorial_flags = '{"first_visit":true,"mission_shown":true,"first_mission":true,"stats_spent":true,"shop_shown":true,"first_item":true,"duel_shown":true,"first_duel":true,"tutorial_finished":true}';
                $player->givePremium(Config::get('constants.tutorial_finished_premium_currency'));
            }
		}
		
		$player->setItemInInventory($shop_item, $target_slotname);
		$player->setItemInInventory(null, $shopslot);
	    if($shop_item->premium_item)
	        $player->givePremium(-$shop_item->buy_price);
	    else{
	        $player->giveMoney(-$shop_item->buy_price);
	        $player->incrementGoalStat('coins_spent_a_day', $shop_item->buy_price);
	    }
	    $player->incrementGoalStat('items_collected');
	    Utils::addItemToOwnedTemplates($player, $shop_item);
	    Utils::addItemToPattern($player, $shop_item);
	    if($shop_item->quality >= 2) $player->incrementGoalStat('rare_items_bought');
	    if($shop_item->quality >= 3) $player->incrementGoalStat('epic_items_bought');
	        
	    $player->calculateStats();
	    Core::req()->data = array(
	        'character'=>$player->character,
	        'inventory'=>["id"=> $player->inventory->id, $target_slotname => $shop_item->id, $shopslot => 0],
	    );
	    if($replaceData != false)
	    	Core::req()->data['inventory'][$replaceData[0]] = $replaceData[1];
    }
}