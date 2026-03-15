<?php
/**
 * Tournament Lifecycle CLI
 *
 * Usage:
 *   php process-tournament.php start    - Start a new weekly tournament (snapshot all characters)
 *   php process-tournament.php end      - End active tournament (calculate rankings, distribute rewards)
 *   php process-tournament.php status   - Show current tournament status
 */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);

define('IN_ENGINE', TRUE);
define('BASE_DIR', __DIR__.'/..');
define('SERVER_DIR', __DIR__);
define('CACHE_DIR', SERVER_DIR.'/cache');

ob_start();
require_once(SERVER_DIR.'/src/Utils/functions.php');
require_once(SERVER_DIR.'/src/Utils/autoloader.php');
\Srv\Config::__init();
\Srv\DB::__init();
ob_end_clean();

$command = $argv[1] ?? 'status';

switch($command){
    case 'start':
        startTournament();
        break;
    case 'end':
        endTournament();
        break;
    case 'status':
        showStatus();
        break;
    default:
        echo "Usage: php process-tournament.php [start|end|status]\n";
}

function startTournament(){
    $active = \Srv\DB::sql("SELECT id FROM tournaments WHERE status = 1")->fetch(\PDO::FETCH_ASSOC);
    if($active){
        echo "ERROR: Tournament #{$active['id']} is already active. End it first.\n";
        return;
    }

    $endWeekday = \Cls\GameSettings::getConstant('tournament_end_weekday', 'Sunday');
    $endHour = intval(\Cls\GameSettings::getConstant('tournament_end_hour', 21));

    $tsEnd = strtotime("next {$endWeekday} {$endHour}:00:00");
    if($tsEnd <= time())
        $tsEnd = strtotime("+1 week {$endWeekday} {$endHour}:00:00");

    $week = intval(date('W'));

    $tournament = new \Schema\Tournament([
        'week' => $week,
        'ts_start' => time(),
        'ts_end' => $tsEnd,
        'status' => 1,
    ]);
    $tournament->save();
    $tid = $tournament->id;

    $characters = \Srv\DB::sql("SELECT id, xp, honor, guild_id FROM `character`")->fetchAll(\PDO::FETCH_ASSOC);
    $guildHonor = [];

    foreach($characters as $ch){
        $gid = intval($ch['guild_id']);
        if($gid > 0 && !isset($guildHonor[$gid])){
            $gh = \Srv\DB::sql("SELECT honor FROM guild WHERE id = {$gid}")->fetchColumn();
            $guildHonor[$gid] = intval($gh);
        }

        $snapshot = new \Schema\TournamentSnapshot([
            'tournament_id' => $tid,
            'character_id' => intval($ch['id']),
            'guild_id' => $gid,
            'xp_start' => intval($ch['xp']),
            'honor_start' => intval($ch['honor']),
            'guild_honor_start' => $gid > 0 ? $guildHonor[$gid] : 0,
        ]);
        $snapshot->save();
    }

    echo "Tournament #{$tid} started (week {$week})\n";
    echo "  Characters snapshotted: " . count($characters) . "\n";
    echo "  Ends: " . date('Y-m-d H:i:s', $tsEnd) . "\n";
}

function endTournament(){
    $tournament = \Schema\Tournament::find(function($q){ $q->where('status', 1); });
    if(!$tournament){
        echo "ERROR: No active tournament found.\n";
        return;
    }

    $tid = $tournament->id;
    $tournament->status = 2;
    $tournament->save();

    echo "Processing tournament #{$tid} (week {$tournament->week})...\n";

    $rewardConfig = \Cls\GameSettings::getConstant('tournament_rewards', []);

    $characterRankings = calculateCharacterRankings($tid);
    $guildRankings = calculateGuildRankings($tid);

    $characterRewards = [];

    foreach($characterRankings as $charId => $ranks){
        $rewards = [];
        for($t = 1; $t <= 3; $t++){
            $rank = $ranks[$t] ?? 0;
            $value = $ranks["value_{$t}"] ?? 0;
            $reward = findReward($rewardConfig, $t, $rank);
            $rewards[$t] = [
                'rank' => $rank,
                'value' => $value,
                'reward' => $reward,
            ];
        }

        $guildId = $ranks['guild_id'] ?? 0;
        for($t = 4; $t <= 5; $t++){
            if($guildId > 0 && isset($guildRankings[$guildId])){
                $gRank = $guildRankings[$guildId]['rank'] ?? 0;
                $gValue = $guildRankings[$guildId]['value'] ?? 0;
                $reward = findReward($rewardConfig, $t, $gRank);
                $rewards[$t] = [
                    'rank' => $gRank,
                    'value' => $gValue,
                    'reward' => $reward,
                ];
            } else {
                $rewards[$t] = ['rank' => 0, 'value' => 0, 'reward' => null];
            }
        }

        $characterRewards[$charId] = $rewards;
    }

    $count = 0;
    foreach($characterRewards as $charId => $rewards){
        $r = new \Schema\TournamentReward([
            'tournament_id' => $tid,
            'character_id' => $charId,
            'week' => $tournament->week,
            'rewards' => json_encode($rewards),
            'claimed' => 0,
        ]);
        $r->save();

        \Srv\DB::sql("UPDATE `character` SET pending_tournament_rewards = pending_tournament_rewards + 1 WHERE id = ?", [$charId]);
        $count++;
    }

    $tournament->status = 3;
    $tournament->save();

    echo "Tournament #{$tid} finished.\n";
    echo "  Rewards distributed to {$count} characters.\n";
}

function calculateCharacterRankings($tid){
    $rankings = [];

    for($t = 1; $t <= 3; $t++){
        switch($t){
            case 1: $col = 'xp'; $snapCol = 'xp_start'; break;
            case 2:
            case 3: $col = 'honor'; $snapCol = 'honor_start'; break;
        }

        \Srv\DB::sql("SET @rank = 0");
        $rows = \Srv\DB::sql("
            SELECT @rank := @rank+1 as `rank`, ts.character_id, ts.guild_id,
                   GREATEST(0, CAST(ch.`{$col}` AS SIGNED) - CAST(ts.`{$snapCol}` AS SIGNED)) as delta
            FROM tournament_snapshots ts
            JOIN `character` ch ON ch.id = ts.character_id
            WHERE ts.tournament_id = {$tid}
            ORDER BY GREATEST(0, CAST(ch.`{$col}` AS SIGNED) - CAST(ts.`{$snapCol}` AS SIGNED)) DESC, ch.id ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        foreach($rows as $row){
            $cid = intval($row['character_id']);
            if(!isset($rankings[$cid]))
                $rankings[$cid] = ['guild_id' => intval($row['guild_id'])];
            $rankings[$cid][$t] = intval($row['rank']);
            $rankings[$cid]["value_{$t}"] = max(0, intval($row['delta']));
        }
    }

    return $rankings;
}

function calculateGuildRankings($tid){
    \Srv\DB::sql("SET @rank = 0");
    $rows = \Srv\DB::sql("
        SELECT @rank := @rank+1 as `rank`, g.id as guild_id,
               GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) as delta
        FROM tournament_snapshots ts
        JOIN guild g ON g.id = ts.guild_id
        WHERE ts.tournament_id = {$tid} AND ts.guild_id > 0
        GROUP BY ts.guild_id
        ORDER BY GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) DESC, g.id ASC
    ")->fetchAll(\PDO::FETCH_ASSOC);

    $rankings = [];
    foreach($rows as $row){
        $rankings[intval($row['guild_id'])] = [
            'rank' => intval($row['rank']),
            'value' => max(0, intval($row['delta'])),
        ];
    }
    return $rankings;
}

function findReward($config, $tournamentType, $rank){
    if($rank <= 0) return null;
    foreach($config as $entry){
        if($entry['tournament_type'] == $tournamentType &&
           $rank >= $entry['rank_start'] && $rank <= $entry['rank_end']){
            return [
                'type' => $entry['reward_type'],
                'amount' => $entry['reward_amount'],
            ];
        }
    }
    return null;
}

function showStatus(){
    $active = \Srv\DB::sql("SELECT * FROM tournaments WHERE status = 1")->fetch(\PDO::FETCH_ASSOC);
    if($active){
        $snapshots = \Srv\DB::sql("SELECT COUNT(*) FROM tournament_snapshots WHERE tournament_id = {$active['id']}")->fetchColumn();
        echo "ACTIVE Tournament #{$active['id']} (week {$active['week']})\n";
        echo "  Started: " . date('Y-m-d H:i:s', $active['ts_start']) . "\n";
        echo "  Ends: " . date('Y-m-d H:i:s', $active['ts_end']) . "\n";
        echo "  Participants: {$snapshots}\n";
    } else {
        echo "No active tournament.\n";
    }

    $lastFinished = \Srv\DB::sql("SELECT * FROM tournaments WHERE status = 3 ORDER BY id DESC LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
    if($lastFinished){
        $rewards = \Srv\DB::sql("SELECT COUNT(*) FROM tournament_rewards WHERE tournament_id = {$lastFinished['id']}")->fetchColumn();
        $unclaimed = \Srv\DB::sql("SELECT COUNT(*) FROM tournament_rewards WHERE tournament_id = {$lastFinished['id']} AND claimed = 0")->fetchColumn();
        echo "Last finished: #{$lastFinished['id']} (week {$lastFinished['week']})\n";
        echo "  Rewards: {$rewards} total, {$unclaimed} unclaimed\n";
    }
}
