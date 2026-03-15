<?php
namespace Request;

use Srv\Core;
use Cls\Utils;
use Cls\Utils\ItemsList;
use Cls\Utils\Item;
use Cls\GameSettings;

class sewInventoryItem{

    public function __request($player){
		$sewingMachineReqLevel = GameSettings::getConstant('sewing_machine_req_level');
		if($player->getLVL() < $sewingMachineReqLevel) return;

		$identifier = getField('target_identifier', FIELD_IDENTIFIER);
		$item_id = getField('item_id', FIELD_NUM);

		$itemP = $player->getItemById($item_id);
		if($itemP == null) return Core::setError("invItemId");

		$selType = null;
		for($type = 1; $type <= 7; $type++){
			$ownedItemTemplates = Utils::getOwnedItemTemplates($player, $type);
			if(!$ownedItemTemplates) continue;
			if(in_array($identifier, $ownedItemTemplates)){
				$selType = $type;
				break;
			}
		}

		if(!$selType) return Core::setError('errSewItemNotFound');

		$sewingMachineCommonGameCurrencyFactor = GameSettings::getConstant('sewing_machine_common_game_currency_factor');
		$sewingMachineRarePremiumCurrencyAmount = GameSettings::getConstant('sewing_machine_rare_premium_currency_amount');
		$sewingMachineEpicPremiumCurrencyAmount = GameSettings::getConstant('sewing_machine_epic_premium_currency_amount');
		$sewingMachineSetitemPremiumCurrencyAmount = GameSettings::getConstant('sewing_machine_setitem_premium_currency_amount');

		$priceCurrency = $pricePremium = 0;
		$typeName = Item::$TYPE[$selType];

		$itemData = null;
		foreach(ItemsList::$ITEMS[$typeName] as $item){
			if($item['identifier'] == $identifier){
				$itemData = $item;
				break;
			}
		}

		if(!$itemData) return Core::setError('errSewItemNotFound');

		if(strpos($itemData['identifier'], 'setitem') !== false){
			$pricePremium = $sewingMachineSetitemPremiumCurrencyAmount;
		} else {
			switch($itemData['quality']){
				case 1:
					$priceCurrency = $itemP->sell_price * $sewingMachineCommonGameCurrencyFactor;
					break;
				case 2:
					$pricePremium = $sewingMachineRarePremiumCurrencyAmount;
					break;
				case 3:
					$pricePremium = $sewingMachineEpicPremiumCurrencyAmount;
					break;
			}
		}

		if($player->getPremium() < $pricePremium)
			return Core::setError("errRemovePremiumCurrencyNotEnough");
		if($player->getMoney() < $priceCurrency)
			return Core::setError("errRemoveGameCurrencyNotEnough");

		$player->giveMoney(-$priceCurrency);
		$player->givePremium(-$pricePremium);

		$itemP->identifier = $identifier;
		$itemP->quality = $itemData['quality'];

		Core::req()->data = array(
			"user"=>$player->user,
			"character"=>$player->character,
			"item" => [
				"id" => $itemP->id,
				"identifier" => $itemP->identifier
			]
		);

		$player->incrementGoalStat('item_sewed');
		$goalStats = $player->getGoalStats();
		if(($goalStats['item_sewed'] ?? 0) == 1){
			$player->setGoalStat('first_item_sewed', 1);
		}
    }
}
