<?php
namespace Request;

use Srv\Core;
use Cls\Utils;

class getOwnedInventoryItems{

    public function __request($player){
		$itemtype = intval(getField('item_type', FIELD_NUM));
		$itemquality = getField('item_quality', FIELD_NUM);
		$itemquality = ($itemquality !== null && $itemquality !== '') ? intval($itemquality) : null;

		Core::req()->data = array(
			"user"=>$player->user
		);

		Core::req()->data['owned_item_templates'] = Utils::getOwnedItemTemplates($player, $itemtype, $itemquality);
    }
}
