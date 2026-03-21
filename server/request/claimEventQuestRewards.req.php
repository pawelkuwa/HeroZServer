<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Cls\Utils;
use Cls\GameSettings;

class claimEventQuestRewards{

    public function __request($player){
        $rewardItemId = intval(getField('reward_item_id', FIELD_NUM));
        $discardItem = getField('discard_item') == 'true';

        if($player->character->event_quest_id <= 0)
            return Core::setError('errEventQuestNotActive');

        $eq = $player->getActiveEventQuestRecord();
        if(!$eq)
            return Core::setError('errEventQuestNotActive');

        $events = GameSettings::getConstant('event_quests');
        $event = $events[$eq->identifier] ?? null;
        if(!$event)
            return Core::setError('errEventQuestNotFound');

        $now = time();
        $endTs = strtotime($eq->end_date);
        $expired = ($endTs && $endTs < $now);

        $allCollected = true;
        foreach($event['objectives'] as $obj){
            $idx = $obj['index'];
            $field = 'objective' . $idx . '_value';
            if($eq->{$field} <= $obj['value']){
                $allCollected = false;
                break;
            }
        }

        if(!$allCollected && !$expired && $eq->status == 1)
            return Core::setError('errEventQuestNotComplete');

        $lvl = $player->getLVL();
        $rewards = json_decode($eq->rewards, true) ?: [];
        $rewardFactor = 1.0;

        if($allCollected){
            $eq->status = 2;
        } else {
            $eq->status = 4;
            $rewardFactor = 0.5;
        }

        foreach($rewards as $reward){
            $this->giveMainReward($player, $reward, $lvl, $rewardFactor);
        }

        $items = [];
        if(!$discardItem && $rewardItemId > 0){
            $slot = $player->findEmptyInventorySlot();
            if($slot){
                $item = $player->getItemById($rewardItemId);
                if($item){
                    $player->setItemInInventory($item, $slot);
                    Utils::addItemToOwnedTemplates($player, $item);
                    Utils::addItemToPattern($player, $item);
                    $items[] = $item;
                }
            }
        }

        // Discard unchosen reward items
        for($i = 1; $i <= 3; $i++){
            $itemId = $eq->{'reward_item' . $i . '_id'};
            if($itemId > 0 && $itemId != $rewardItemId){
                $item = $player->getItemById($itemId);
                if($item) $player->removeItem($item);
            }
        }

        $player->character->event_quest_id = 0;
        $eq->save();

        Core::req()->data = [
            'event_quest' => [
                'id' => $eq->id,
                'identifier' => $eq->identifier,
                'status' => $eq->status,
            ],
            'character' => $player->character,
            'user' => $player->user,
            'items' => $items,
        ];

        if($slot ?? null)
            Core::req()->data['inventory'] = ['id' => $player->inventory->id, $slot => $rewardItemId];
    }

    private function giveMainReward($player, $reward, $lvl, $factor){
        $type = $reward['type'] ?? 0;
        $base = $reward['factor'] ?? 1;
        $amount = round($base * $factor);

        switch($type){
            case 1: // game_currency
                $cpt = Utils::coinsPerTime($lvl);
                $coins = round($cpt * $amount * 600) + pow($lvl, 1.9);
                $player->giveMoney(max(round($coins), 1));
                break;
            case 2: // premium_currency
                $player->givePremium(max($amount, 1));
                break;
            case 3: // stat_points
                $player->character->stat_points_available += max($amount, 1);
                break;
            case 4: // xp
                $levels = Config::get('constants.levels');
                $xpForLevel = isset($levels[$lvl]) ? (int)$levels[$lvl]['xp'] : 0;
                $xpNext = isset($levels[$lvl + 1]) ? (int)$levels[$lvl + 1]['xp'] : $xpForLevel * 2;
                $xpNeeded = $xpNext - $xpForLevel;
                $xp = round($xpNeeded * 0.1 * $amount);
                $player->giveExp(max($xp, 1));
                break;
        }
    }
}
