<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\Utils;
use Cls\GameSettings;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\WorldbossReward;

class claimWorldbossEventRewards{
    public function __request($player){
        $eventId = intval(getField('worldboss_event_id', FIELD_NUM));
        $discardItem = getField('discard_item', FIELD_BOOL) == 'true';

        if(!$eventId)
            return Core::setError('errApplyInvalidStatus');

        $event = WorldbossEvent::find(function($q) use($eventId){ $q->where('id', $eventId); });
        if(!$event || ($event->status != 2 && $event->status != 4))
            return Core::setError('errApplyInvalidStatus');

        $reward = WorldbossReward::find(function($q) use($eventId, $player){
            $q->where('worldboss_event_id', $eventId)->where('character_id', $player->character->id);
        });
        if(!$reward)
            return Core::setError('errApplyInvalidStatus');

        if($reward->item_id > 0 && !$discardItem){
            $freeSlot = $player->findEmptyInventorySlot();
            if($freeSlot === null)
                return Core::setError('errInventoryNoEmptySlot');
            $item = $player->getItemById($reward->item_id);
            if($item)
                $player->setItemInInventory($item, $freeSlot);
        }

        $player->giveMoney($reward->game_currency);
        $player->giveExp($reward->xp);
        $player->character->quest_energy += $reward->quest_energy;
        $player->character->training_count += $reward->training_sessions;

        $player->incrementGoalStat('worldboss_events_won');

        $reward->remove();

        Core::req()->data = [
            'character' => $player->character,
            'inventory' => $player->inventory,
            'items' => $player->items,
        ];
    }
}
