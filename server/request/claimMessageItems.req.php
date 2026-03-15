<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Schema\Messages;
use Schema\Items;

class claimMessageItems{
    public function __request($player){
        $msgId = intval(getField('message_id', FIELD_NUM));
        $discard = getField('discard_item') === 'true';

        $msg = Messages::find(function($q)use($msgId){ $q->where('id', $msgId); });
        if(!$msg || empty($msg->flag_value))
            return Core::setError('');

        $itemIds = array_filter(explode(';', $msg->flag_value), 'strlen');
        if(empty($itemIds))
            return Core::setError('');

        if(!$discard){
            foreach($itemIds as $itemId){
                $slot = $player->findEmptyInventorySlot();
                if(!$slot)
                    return Core::setError('errInventoryNoEmptySlot');
                $player->inventory->$slot = intval($itemId);
            }
        }else{
            foreach($itemIds as $itemId){
                $item = Items::find(function($q)use($itemId){ $q->where('id', intval($itemId)); });
                if($item){
                    $player->giveMoney($item->sell_price);
                    $item->remove();
                }
            }
        }

        $msg->flag_value = '';

        Core::req()->data = [
            'message' => $msg,
            'character' => $player->character
        ];
    }
}
