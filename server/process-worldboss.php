<?php
ob_start();

define('IN_ENGINE', TRUE);
define('BASE_DIR', __DIR__.'/..');
define('SERVER_DIR', __DIR__);
define('CACHE_DIR', SERVER_DIR.'/cache');

require_once(SERVER_DIR.'/src/Utils/functions.php');
require_once(SERVER_DIR.'/src/Utils/field.php');
require_once(SERVER_DIR.'/src/Utils/autoloader.php');

use Srv\DB;
use Srv\Config;
use Cls\GameSettings;
use Cls\Utils;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\WorldbossReward;
use Schema\Items;

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

Config::__init();
DB::__init();
ob_end_clean();

$action = $argv[1] ?? 'check';

switch($action){
    case 'spawn':
        spawnEvent();
        break;
    case 'process':
        processEvents();
        break;
    case 'check':
        checkEvents();
        break;
    default:
        echo "Usage: php process-worldboss.php [spawn|process|check]\n";
}

function spawnEvent(){
    $identifier = 'olympia_event_stage2';
    $npcIdentifier = 'npc_olympia_worldboss';

    $active = WorldbossEvent::find(function($q){ $q->where('status', 1); });
    if($active){
        echo "Active event already exists (ID: {$active->id})\n";
        return;
    }

    $playerCount = max(100, DB::sql("SELECT COUNT(*) FROM `user` WHERE ts_last_login > " . (time() - 86400 * 7))->fetchColumn());
    $avgDamage = GameSettings::getConstant('worldboss_event_min_avg_damage_per_worldboss_attack') ?: 1000;
    $attacksPerHour = GameSettings::getConstant('worldboss_event_average_attacks_per_dau_per_hour') ?: 3;
    $duration = 4;
    $totalHP = round($playerCount * $attacksPerHour * $duration * $avgDamage);

    $event = new WorldbossEvent([
        'identifier' => $identifier,
        'status' => 1,
        'stage' => 1,
        'min_level' => 10,
        'max_level' => 999,
        'npc_identifier' => $npcIdentifier,
        'npc_hitpoints_total' => $totalHP,
        'npc_hitpoints_current' => $totalHP,
        'attack_count' => 0,
        'ts_end' => time() + ($duration * 3600),
        'reward_top_rank_item_identifier' => 'suit_frogman1',
        'reward_top_pool_item_identifier' => 'mask_frogman1',
    ]);
    $event->save();
    echo "Spawned event '{$identifier}' (ID: {$event->id}, HP: {$totalHP}, ends in {$duration}h)\n";
}

function processEvents(){
    $events = WorldbossEvent::findAll(function($q){
        $q->where('status', 1)->where('ts_end', '<=', time());
        $q->orWhere(function($q){ $q->where('status', 2); });
    });

    foreach($events as $event){
        if($event->status == 1 && $event->ts_end <= time()){
            $defeated = $event->npc_hitpoints_current <= 0;
            $event->status = $defeated ? 2 : 4;
            $event->save();
            if(!$defeated){
                echo "Event {$event->id} expired (boss fled, HP: {$event->npc_hitpoints_current}/{$event->npc_hitpoints_total})\n";
            }
        }

        if($event->status == 2){
            generateRewards($event);
            $event->status = 4;
            $event->save();
            echo "Processed rewards for event {$event->id}\n";
        }
    }
}

function generateRewards($event){
    $defeated = $event->npc_hitpoints_current <= 0;
    $rows = DB::sql("SELECT character_id, COUNT(*) as cnt, SUM(total_damage) as dmg FROM worldboss_attack WHERE worldboss_event_id = {$event->id} AND status = 3 GROUP BY character_id ORDER BY dmg DESC")->fetchAll(\PDO::FETCH_ASSOC);

    $coinBase = GameSettings::getConstant('worldboss_event_reward_coin_base') ?: 1;
    $coinDuration = GameSettings::getConstant('worldboss_event_reward_coin_duration') ?: 300;
    $coinFalloff = GameSettings::getConstant('worldboss_event_reward_coin_falloff') ?: 0.97;
    $coinFledFactor = GameSettings::getConstant('worldboss_event_reward_coin_factor_fled') ?: 0.5;
    $xpBase = GameSettings::getConstant('worldboss_event_reward_xp_base') ?: 1;
    $xpDuration = GameSettings::getConstant('worldboss_event_reward_xp_duration') ?: 300;
    $xpFalloff = GameSettings::getConstant('worldboss_event_reward_xp_falloff') ?: 0.97;
    $xpFledFactor = GameSettings::getConstant('worldboss_event_reward_xp_factor_fled') ?: 0.5;

    foreach($rows as $rank => $row){
        $charId = $row['character_id'];
        $attackCount = $row['cnt'];

        $lvlRow = DB::sql("SELECT level FROM `character` WHERE id = {$charId}")->fetch(\PDO::FETCH_ASSOC);
        $lvl = $lvlRow ? $lvlRow['level'] : 1;

        $totalCoins = 0;
        $totalXp = 0;
        for($i = 0; $i < $attackCount; $i++){
            $totalCoins += floor(Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $i));
            $totalXp += floor(Utils::coinsPerTime($lvl) * $xpBase * $xpDuration * pow($xpFalloff, $i) * 1.5);
        }
        if(!$defeated){
            $totalCoins = floor($totalCoins * $coinFledFactor);
            $totalXp = floor($totalXp * $xpFledFactor);
        }

        $questEnergy = 0;
        $trainingSessions = 0;
        $minAdditional = GameSettings::getConstant('worldboss_event_reward_additional_min_attacks') ?: 5;
        if($defeated && $attackCount >= $minAdditional){
            $questEnergy = mt_rand(1, 3);
            $trainingSessions = mt_rand(0, 2);
        }

        $reward = new WorldbossReward([
            'worldboss_event_id' => $event->id,
            'character_id' => $charId,
            'game_currency' => $totalCoins,
            'xp' => $totalXp,
            'item_id' => 0,
            'sidekick_item_id' => 0,
            'quest_energy' => $questEnergy,
            'training_sessions' => $trainingSessions,
            'rewards' => '',
        ]);
        $reward->save();
    }
}

function checkEvents(){
    $active = WorldbossEvent::find(function($q){ $q->where('status', 1); });
    if($active){
        $remaining = $active->ts_end - time();
        $hp = $active->npc_hitpoints_current;
        $total = $active->npc_hitpoints_total;
        $pct = $total > 0 ? round(($hp / $total) * 100, 1) : 0;
        echo "Active: {$active->identifier} (ID: {$active->id})\n";
        echo "HP: {$hp}/{$total} ({$pct}%)\n";
        echo "Attacks: {$active->attack_count}\n";
        echo "Remaining: " . gmdate("H:i:s", max(0, $remaining)) . "\n";
    } else {
        echo "No active worldboss event\n";
    }

    $pending = WorldbossEvent::find(function($q){ $q->where('status', 2); });
    if($pending)
        echo "Pending rewards: Event {$pending->id}\n";
}
