<?php
namespace Request;

use Srv\Core;
use Cls\GameSettings;

class instantFinishEventQuestObjective{

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

        if($current >= $target)
            return Core::setError('errEventQuestObjectiveAlreadyComplete');

        $progress = $current / max($target, 1);
        $cost = ceil((1 - $progress) * 29);
        $cost = max($cost, 1);

        if($player->getPremium() < $cost)
            return Core::setError('errRemovePremiumCurrencyNotEnough');

        $player->givePremium(-$cost);
        $eq->{$field} = $target;
        $eq->save();

        Core::req()->data = [
            'event_quest' => $player->getEventQuest(),
            'character' => $player->character,
            'user' => $player->user,
        ];
    }
}
