<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\GameSettings;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\Battle;

class finishWorldbossAttack{
    public function __request($player){
        $eventId = intval(getField('worldboss_event_id', FIELD_NUM));
        if(!$eventId)
            return Core::setError('errApplyInvalidStatus');

        $event = WorldbossEvent::find(function($q) use($eventId){ $q->where('id', $eventId); });
        if(!$event)
            return Core::setError('errApplyInvalidStatus');

        $attack = WorldbossAttack::find(function($q) use($eventId, $player){
            $q->where('worldboss_event_id', $eventId)->where('character_id', $player->character->id)->where('status', 3);
            $q->orderBy('id', 'desc')->limit(1);
        });
        if(!$attack)
            return Core::setError('errApplyInvalidStatus');

        $battle = null;
        if($attack->battle_id){
            $battle = Battle::find(function($q) use($attack){ $q->where('id', $attack->battle_id); });
        }

        $ownAttacks = WorldbossAttack::findAll(function($q) use($event, $player){
            $q->where('worldboss_event_id', $event->id)->where('character_id', $player->character->id)->where('status', 3);
        });
        $ownAttackCount = count($ownAttacks);

        Core::req()->data = [
            'character' => $player->character,
            'worldboss_attack' => $attack,
            'battle' => $battle,
            'worldboss_event' => [
                'id' => $event->id,
                'npc_hitpoints_current' => $event->npc_hitpoints_current,
                'attack_count' => $event->attack_count,
                'status' => $event->status,
                'top_attacker_name' => $event->top_attacker_name,
                'top_attacker_count' => $event->top_attacker_count,
                'winning_attacker_name' => $event->winning_attacker_name,
            ],
            'worldboss_event_character_data' => [$this->getCharacterData($event, $player, $ownAttackCount)],
        ];
    }

    private function getCharacterData($event, $player, $ownAttackCount){
        $lvl = $player->getLVL();
        $coinBase = GameSettings::getConstant('worldboss_event_reward_coin_base') ?: 1;
        $coinDuration = GameSettings::getConstant('worldboss_event_reward_coin_duration') ?: 300;
        $coinFalloff = GameSettings::getConstant('worldboss_event_reward_coin_falloff') ?: 0.97;
        $xpBase = GameSettings::getConstant('worldboss_event_reward_xp_base') ?: 1;
        $xpDuration = GameSettings::getConstant('worldboss_event_reward_xp_duration') ?: 300;
        $xpFalloff = GameSettings::getConstant('worldboss_event_reward_xp_falloff') ?: 0.97;

        $coinTotal = 0;
        $xpTotal = 0;
        for($i = 0; $i < $ownAttackCount; $i++){
            $coinTotal += floor(\Cls\Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $i));
            $xpTotal += floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, $i));
        }
        $nextCoin = floor(\Cls\Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $ownAttackCount));
        $nextXp = floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, $ownAttackCount));

        return [
            'worldboss_event_id' => $event->id,
            'ranking' => $this->getPlayerRanking($event, $player),
            'coin_reward_total' => $coinTotal,
            'coin_reward_next_attack' => $nextCoin,
            'xp_reward_total' => $xpTotal,
            'xp_reward_next_attack' => $nextXp,
        ];
    }

    private function getPlayerRanking($event, $player){
        $rows = DB::sql("SELECT character_id, SUM(total_damage) as dmg FROM worldboss_attack WHERE worldboss_event_id = {$event->id} AND status = 3 GROUP BY character_id ORDER BY dmg DESC")->fetchAll(\PDO::FETCH_ASSOC);
        foreach($rows as $i => $row){
            if($row['character_id'] == $player->character->id)
                return $i + 1;
        }
        return 0;
    }
}
