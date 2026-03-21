<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Cls\Utils;
use Cls\Utils\Item;
use Cls\GameSettings;
use Schema\EventQuests;

class assignEventQuest{

    public function __request($player){
        $identifier = getField('event_quest_identifier');
        if(!$identifier)
            return Core::setError('errEventQuestNotFound');

        $minLevel = GameSettings::getConstant('event_quest_min_level');
        if($player->getLVL() < $minLevel)
            return Core::setError('errEventQuestLevelTooLow');

        if($player->character->event_quest_id != 0)
            return Core::setError('errEventQuestAlreadyActive');

        $events = GameSettings::getConstant('event_quests');
        if(!isset($events[$identifier]))
            return Core::setError('errEventQuestNotFound');

        $event = $events[$identifier];
        $now = time();
        $start = strtotime($event['start_date']);
        $end = strtotime($event['end_date']);
        if(!$start || !$end || $now < $start || $now > $end)
            return Core::setError('errEventQuestNotActive');

        $lvl = $player->getLVL();
        $rewardItems = [];
        $rewardItemIds = [];

        for($i = 1; $i <= 3; $i++){
            $itemIdentifier = $event['reward_item' . $i];
            $levelPlus = $event['reward_item' . $i . '_level_plus'] ?? 0;
            $itemLvl = $lvl + $levelPlus;

            if($itemIdentifier == 'random_epic'){
                $item = $this->generateRandomItem($player, $itemLvl, 3);
            } elseif($itemIdentifier == 'random_rare'){
                $item = $this->generateRandomItem($player, $itemLvl, 2);
            } elseif($itemIdentifier == 'random_common'){
                $item = $this->generateRandomItem($player, $itemLvl, 1);
            } else {
                $item = $this->createEventItem($player, $itemIdentifier, $itemLvl);
                if(!$item) $item = $this->generateRandomItem($player, $itemLvl, 3);
            }

            if($item){
                $rewardItems[] = $item;
                $rewardItemIds[$i] = $item->id;
            } else {
                $rewardItemIds[$i] = 0;
            }
        }

        $rewards = json_encode([
            ['type' => $event['reward1_type'], 'factor' => $event['reward1_factor'], 'flag' => $event['reward1_flag']],
            ['type' => $event['reward2_type'], 'factor' => $event['reward2_factor'], 'flag' => $event['reward2_flag']],
        ]);

        $eq = new EventQuests([
            'character_id' => $player->character->id,
            'identifier' => $identifier,
            'status' => 1,
            'end_date' => $event['end_date'],
            'rewards' => $rewards,
            'reward_item1_id' => $rewardItemIds[1],
            'reward_item2_id' => $rewardItemIds[2],
            'reward_item3_id' => $rewardItemIds[3],
            'ts_creation' => time(),
        ]);
        $eq->save();

        $player->character->event_quest_id = $eq->id;

        Core::req()->data = [
            'event_quest' => [
                'id' => $eq->id,
                'identifier' => $eq->identifier,
                'status' => $eq->status,
                'end_date' => $eq->end_date,
                'objective1_value' => 0,
                'objective2_value' => 0,
                'objective3_value' => 0,
                'objective4_value' => 0,
                'objective5_value' => 0,
                'objective6_value' => 0,
                'rewards' => $eq->rewards,
                'reward_item1_id' => $eq->reward_item1_id,
                'reward_item2_id' => $eq->reward_item2_id,
                'reward_item3_id' => $eq->reward_item3_id,
            ],
            'character' => $player->character,
            'items' => $rewardItems,
        ];
    }

    private function generateRandomItem($player, $lvl, $quality){
        $type = mt_rand(1, 7);
        $typeName = Item::$TYPE[$type];
        $templates = GameSettings::getConstant('item_templates');

        $candidates = [];
        foreach($templates as $id => $tpl){
            if($tpl['type'] == $type && $tpl['quality'] == $quality && !$tpl['is_license'])
                $candidates[] = $id;
        }

        if(empty($candidates)) return null;
        $chosen = $candidates[array_rand($candidates)];
        $tpl = $templates[$chosen];
        $item = $player->createItem([
            'identifier' => $chosen,
            'type' => $tpl['type'],
            'quality' => $tpl['quality'],
            'required_level' => $tpl['required_level'] ?? 1,
        ]);
        if($item) Utils::randomiseItem($item, $lvl);
        return $item;
    }

    private function createEventItem($player, $identifier, $lvl){
        $templates = GameSettings::getConstant('item_templates');
        $tpl = $templates[$identifier] ?? null;
        if(!$tpl) return null;

        $item = $player->createItem([
            'identifier' => $identifier,
            'type' => $tpl['type'],
            'quality' => $tpl['quality'],
            'required_level' => $tpl['required_level'] ?? 1,
        ]);
        if($item) Utils::randomiseItem($item, $lvl);
        return $item;
    }
}
