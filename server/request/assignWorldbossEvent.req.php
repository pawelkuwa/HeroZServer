<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\WorldbossReward;
use Cls\GameSettings;

class assignWorldbossEvent{
    public function __request($player){
        $eventId = intval(getField('worldboss_event_id', FIELD_NUM));
        if(!$eventId)
            return Core::setError('errApplyInvalidStatus');

        $event = WorldbossEvent::find(function($q) use($eventId){ $q->where('id', $eventId); });
        if(!$event)
            return Core::setError('errApplyInvalidStatus');

        if($event->status != 1 && $event->status != 2 && $event->status != 4)
            return Core::setError('errApplyInvalidStatus');

        $player->character->worldboss_event_id = $event->id;

        $attacks = WorldbossAttack::findAll(function($q) use($eventId, $player){
            $q->where('worldboss_event_id', $eventId)->where('character_id', $player->character->id);
        });
        $ownAttackCount = count($attacks);

        $reward = WorldbossReward::find(function($q) use($eventId, $player){
            $q->where('worldboss_event_id', $eventId)->where('character_id', $player->character->id);
        });

        $coinTotal = 0;
        $xpTotal = 0;
        foreach($attacks as $atk){
            if($atk->status == 3){
                $coinTotal += $this->calcCoinReward($player, $atk, $event);
                $xpTotal += $this->calcXpReward($player, $atk, $event);
            }
        }

        $eventData = [
            'id' => $event->id,
            'identifier' => $event->identifier,
            'status' => $event->status,
            'stage' => $event->stage,
            'min_level' => $event->min_level,
            'max_level' => $event->max_level,
            'npc_identifier' => $event->npc_identifier,
            'npc_hitpoints_total' => $event->npc_hitpoints_total,
            'npc_hitpoints_current' => $event->npc_hitpoints_current,
            'attack_count' => $event->attack_count,
            'ts_end' => $event->ts_end,
            'ranking' => $this->getPlayerRanking($event, $player),
            'coin_reward_total' => $coinTotal,
            'coin_reward_next_attack' => $this->calcNextCoinReward($player, $ownAttackCount, $event),
            'xp_reward_total' => $xpTotal,
            'xp_reward_next_attack' => $this->calcNextXpReward($player, $ownAttackCount, $event),
            'top_attacker_name' => $event->top_attacker_name,
            'top_attacker_count' => $event->top_attacker_count,
            'winning_attacker_name' => $event->winning_attacker_name,
            'reward_top_rank_item_identifier' => $event->reward_top_rank_item_identifier,
            'reward_top_pool_item_identifier' => $event->reward_top_pool_item_identifier,
        ];

        Core::req()->data = [
            'character' => $player->character,
            'worldboss_event_character_data' => [$eventData],
            'worldboss_attacks_character_data' => $attacks,
        ];

        if($reward)
            Core::req()->data['worldboss_reward_character_data'] = [$reward];
    }

    private function getPlayerRanking($event, $player){
        $rows = \Srv\DB::sql("SELECT character_id, SUM(total_damage) as dmg FROM worldboss_attack WHERE worldboss_event_id = {$event->id} AND status = 3 GROUP BY character_id ORDER BY dmg DESC")->fetchAll(\PDO::FETCH_ASSOC);
        foreach($rows as $i => $row){
            if($row['character_id'] == $player->character->id)
                return $i + 1;
        }
        return 0;
    }

    private function calcCoinReward($player, $attack, $event){
        $base = GameSettings::getConstant('worldboss_event_reward_coin_base') ?: 1;
        $duration = GameSettings::getConstant('worldboss_event_reward_coin_duration') ?: 300;
        $falloff = GameSettings::getConstant('worldboss_event_reward_coin_falloff') ?: 0.97;
        $lvl = $player->getLVL();
        return floor(\Cls\Utils::coinsPerTime($lvl) * $base * $duration * pow($falloff, max(0, $attack->id - 1)));
    }

    private function calcXpReward($player, $attack, $event){
        $base = GameSettings::getConstant('worldboss_event_reward_xp_base') ?: 1;
        $duration = GameSettings::getConstant('worldboss_event_reward_xp_duration') ?: 300;
        $falloff = GameSettings::getConstant('worldboss_event_reward_xp_falloff') ?: 0.97;
        $lvl = $player->getLVL();
        return floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $base * $duration * pow($falloff, max(0, $attack->id - 1)));
    }

    private function calcNextCoinReward($player, $ownAttackCount, $event){
        $base = GameSettings::getConstant('worldboss_event_reward_coin_base') ?: 1;
        $duration = GameSettings::getConstant('worldboss_event_reward_coin_duration') ?: 300;
        $falloff = GameSettings::getConstant('worldboss_event_reward_coin_falloff') ?: 0.97;
        $lvl = $player->getLVL();
        return floor(\Cls\Utils::coinsPerTime($lvl) * $base * $duration * pow($falloff, $ownAttackCount));
    }

    private function calcNextXpReward($player, $ownAttackCount, $event){
        $base = GameSettings::getConstant('worldboss_event_reward_xp_base') ?: 1;
        $duration = GameSettings::getConstant('worldboss_event_reward_xp_duration') ?: 300;
        $falloff = GameSettings::getConstant('worldboss_event_reward_xp_falloff') ?: 0.97;
        $lvl = $player->getLVL();
        return floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $base * $duration * pow($falloff, $ownAttackCount));
    }
}
