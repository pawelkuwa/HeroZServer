<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Srv\Socket;
use Cls\GameSettings;
use Cls\Utils;
use Cls\Entity;
use Cls\Fight;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\Battle;

class checkForWorldbossAttackComplete{
    public function __request($player){
        $attack = WorldbossAttack::find(function($q) use($player){
            $q->where('character_id', $player->character->id)->where('status', 1);
        });
        if(!$attack)
            return Core::setError('errApplyInvalidStatus');

        if($attack->ts_complete > time())
            return Core::setError('errFinishNotYetCompleted');

        $event = WorldbossEvent::find(function($q) use($attack){ $q->where('id', $attack->worldboss_event_id); });
        if(!$event)
            return Core::setError('errApplyInvalidStatus');

        $player->calculateStats();
        $npcFactor = GameSettings::getConstant('worldboss_event_npc_stat_factor') ?: 1.2;
        $lvl = $player->getLVL();

        $npc = new Entity();
        $npc->level = $lvl;
        $npc->stamina = round($player->stamina * $npcFactor);
        $npc->total_stamina = $npc->stamina;
        $npc->hitpoints = $npc->stamina * 10;
        $npc->strength = round($player->strength * $npcFactor);
        $npc->criticalrating = round($player->criticalrating * $npcFactor * 0.5);
        $npc->dodgerating = round($player->dodgerating * $npcFactor * 0.5);
        $npc->damage_normal = round($npc->strength * 0.6);
        $npc->weapondamage = 0;
        $npc->profile = 'worldboss_' . $event->npc_identifier;

        $playerEntity = clone $player;
        $profileAStats = cast($playerEntity, '\Cls\Entity');
        $profileBStats = clone $npc;

        $fight = new Fight($playerEntity, $npc, FALSE);
        $fight->fight();

        $damage = max(1, $npc->stamina * 10 - max(0, $npc->hitpoints));

        $attack->total_damage = $damage;
        $attack->status = 3;

        $winner = $npc->hitpoints <= 0 ? 'a' : 'b';

        $battle = new Battle([
            'ts_creation' => time(),
            'character_a_id' => $player->character->id,
            'character_b_id' => 0,
            'profile_a_stats' => json_encode($profileAStats),
            'profile_b_stats' => json_encode($profileBStats),
            'winner' => $winner,
            'rounds' => json_encode($fight->getRounds()),
        ]);
        $battle->save();
        $attack->battle_id = $battle->id;

        DB::sql("UPDATE worldboss_event SET npc_hitpoints_current = GREATEST(0, npc_hitpoints_current - {$damage}), attack_count = attack_count + 1 WHERE id = {$event->id}");

        $event = WorldbossEvent::find(function($q) use($event){ $q->where('id', $event->id); });

        $ownAttacks = WorldbossAttack::findAll(function($q) use($event, $player){
            $q->where('worldboss_event_id', $event->id)->where('character_id', $player->character->id)->where('status', 3);
        });
        $ownAttackCount = count($ownAttacks);

        $this->updateTopAttacker($event, $player, $ownAttackCount);

        Socket::syncWorldboss($event->id, $event->npc_hitpoints_current);

        if($event->npc_hitpoints_current <= 0){
            $event->winning_attacker_name = $player->character->name;
            $event->status = 2;
        }

        $player->incrementGoalStat('worldboss_attacks_completed');
        if($ownAttackCount == 1)
            $player->setGoalStat('first_worldboss_attack_completed', 1);
        $player->updateEventQuestProgress(6, $event->identifier);

        $player->character->active_worldboss_attack_id = 0;

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
            $coinTotal += floor(Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $i));
            $xpTotal += floor(Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, $i));
        }
        $nextCoin = floor(Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $ownAttackCount));
        $nextXp = floor(Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, $ownAttackCount));

        return [
            'worldboss_event_id' => $event->id,
            'ranking' => $this->getPlayerRanking($event, $player),
            'coin_reward_total' => $coinTotal,
            'coin_reward_next_attack' => $nextCoin,
            'xp_reward_total' => $xpTotal,
            'xp_reward_next_attack' => $nextXp,
        ];
    }

    private function updateTopAttacker($event, $player, $ownCount){
        if($ownCount > $event->top_attacker_count){
            $event->top_attacker_character_id = $player->character->id;
            $event->top_attacker_name = $player->character->name;
            $event->top_attacker_count = $ownCount;
        }
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
