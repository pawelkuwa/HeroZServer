<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Cls\Utils;
use Cls\GameSettings;

class claimEventQuestObjectiveRewards{

    public function __request($player){
        $objectiveIdentifier = getField('objective_identifier');
        if(!$objectiveIdentifier)
            return Core::setError('errEventQuestObjectiveNotFound');

        if($player->character->event_quest_id <= 0)
            return Core::setError('errEventQuestNotActive');

        $eq = $player->getActiveEventQuestRecord();
        if(!$eq || $eq->status != 1)
            return Core::setError('errEventQuestNotActive');

        $events = GameSettings::getConstant('event_quests');
        $event = $events[$eq->identifier] ?? null;
        if(!$event)
            return Core::setError('errEventQuestNotFound');

        $objectives = $event['objectives'];
        if(!isset($objectives[$objectiveIdentifier]))
            return Core::setError('errEventQuestObjectiveNotFound');

        $obj = $objectives[$objectiveIdentifier];
        $idx = $obj['index'];
        $field = 'objective' . $idx . '_value';
        $current = $eq->{$field};
        $target = $obj['value'];

        if($current != $target)
            return Core::setError('errEventQuestObjectiveNotComplete');

        $eq->{$field} = $target + 1;

        $lvl = $player->getLVL();
        $this->giveObjectiveReward($player, $obj, $lvl);

        $eq->save();

        Core::req()->data = [
            'event_quest' => $player->getEventQuest(),
            'character' => $player->character,
            'user' => $player->user,
        ];
    }

    private function giveObjectiveReward($player, $obj, $lvl){
        $type = $obj['reward_type'];
        $factor = $obj['reward_factor'];

        switch($type){
            case 1: // game_currency
                $cpt = Utils::coinsPerTime($lvl);
                $coins = round($cpt * $factor * 600) + pow($lvl, 1.9);
                $player->giveMoney(max(round($coins), 1));
                break;
            case 2: // premium_currency
                $donuts = max(1, round($factor * 5));
                $player->givePremium($donuts);
                break;
            case 3: // stat_points
                $points = max(1, round($factor * 5));
                $player->character->stat_points_available += $points;
                break;
            case 4: // xp
                $levels = Config::get('constants.levels');
                $xpForLevel = isset($levels[$lvl]) ? (int)$levels[$lvl]['xp'] : 0;
                $xpNext = isset($levels[$lvl + 1]) ? (int)$levels[$lvl + 1]['xp'] : $xpForLevel * 2;
                $xpNeeded = $xpNext - $xpForLevel;
                $xp = round($xpNeeded * $factor);
                $player->giveExp(max($xp, 1));
                break;
        }
    }
}
