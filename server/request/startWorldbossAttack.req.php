<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Cls\GameSettings;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;

class startWorldbossAttack{
    public function __request($player){
        $eventId = intval(getField('worldboss_event_id', FIELD_NUM));
        if(!$eventId)
            return Core::setError('errApplyInvalidStatus');

        $event = WorldbossEvent::find(function($q) use($eventId){ $q->where('id', $eventId); });
        if(!$event || $event->status != 1)
            return Core::setError('errApplyInvalidStatus');
        if($event->ts_end <= time())
            return Core::setError('errApplyInvalidStatus');
        if($event->npc_hitpoints_current <= 0)
            return Core::setError('errApplyInvalidStatus');

        $lvl = $player->getLVL();
        if($lvl < $event->min_level || $lvl > $event->max_level)
            return Core::setError('errUseItemItemReqLevel');

        $pendingAttack = WorldbossAttack::find(function($q) use($eventId, $player){
            $q->where('worldboss_event_id', $eventId)
              ->where('character_id', $player->character->id)
              ->where('status', 1);
        });
        if($pendingAttack)
            return Core::setError('errStartQuestActiveQuestFound');

        $durationRaw = GameSettings::getConstant('quest_duration_short') ?: 300;
        $questLevelFull = GameSettings::getConstant('quest_level_full_duration') ?: 10;
        if($lvl < $questLevelFull)
            $durationRaw = round($durationRaw * ($lvl / $questLevelFull));

        $duration = $durationRaw;
        $boostSum = 0;
        if(($booster = $player->getBoosters('quest')) != null)
            $boostSum += Config::get("constants.boosters.$booster.amount");
        if($player->character->guild_id != 0 && ($booster = $player->guild->getBoosters('quest')) != null)
            $boostSum += Config::get("constants.guild_boosters.$booster.amount");
        if($boostSum > 0)
            $duration = round($duration * (1 - ($boostSum / 100)));

        $tsComplete = time() + $duration;

        $attack = new WorldbossAttack([
            'worldboss_event_id' => $event->id,
            'character_id' => $player->character->id,
            'status' => 1,
            'ts_complete' => $tsComplete,
            'duration' => $duration,
            'duration_raw' => $durationRaw,
            'total_damage' => 0,
        ]);
        $attack->save();

        $player->character->active_worldboss_attack_id = $attack->id;
        $player->character->worldboss_event_attack_count++;

        Core::req()->data = [
            'character' => $player->character,
            'worldboss_attack' => $attack,
        ];
    }
}
