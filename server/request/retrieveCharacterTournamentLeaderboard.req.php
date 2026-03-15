<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Schema\Tournament;
use PDO;

class retrieveCharacterTournamentLeaderboard{
    public function __request($player){
        $type = intval(getField('type', FIELD_NUM));
        if($type < 1 || $type > 3)
            return Core::setError('errTournamentLocked');

        $tournament = Tournament::find(function($q){ $q->where('status', 1); });
        if(!$tournament)
            return Core::setError('errTournamentLocked');

        $character_name = getField('character_name', FIELD_ALNUM, FALSE);
        $sort_rank = intval(getField('rank', FIELD_NUM, FALSE));

        switch($type){
            case 1: $sortCol = 'xp'; $snapshotCol = 'xp_start'; break;
            case 2: $sortCol = 'honor'; $snapshotCol = 'honor_start'; break;
            case 3: $sortCol = 'honor'; $snapshotCol = 'honor_start'; break;
            default: return Core::setError('errTournamentLocked');
        }

        $tid = $tournament->id;

        $max_ch = DB::sql("SELECT COUNT(*) FROM tournament_snapshots WHERE tournament_id = {$tid}")->fetchColumn();
        $max_ch = intval($max_ch);

        if($max_ch == 0)
            return Core::setError('errRetrieveLeaderboardEmpty');

        if($sort_rank < 0 || $sort_rank > $max_ch)
            return Core::setError('errRetrieveLeaderboardInvalidRank');

        if($sort_rank){
            $centerRank = $sort_rank;
        } else {
            if($character_name){
                DB::sql("SET @rank = 0");
                $centerRank = DB::sql("
                    SELECT ranked.rank FROM (
                        SELECT @rank := @rank+1 as `rank`, ts.character_id, ch.name
                        FROM tournament_snapshots ts
                        JOIN `character` ch ON ch.id = ts.character_id
                        WHERE ts.tournament_id = {$tid}
                        ORDER BY GREATEST(0, CAST(ch.`{$sortCol}` AS SIGNED) - CAST(ts.`{$snapshotCol}` AS SIGNED)) DESC, ch.id ASC
                    ) ranked
                    WHERE ranked.name LIKE ?
                    LIMIT 1
                ", ["%{$character_name}%"])->fetchColumn();
                $centerRank = intval($centerRank);
                if(!$centerRank)
                    return Core::setError('errRetrieveLeaderboardInvalidCharacter');
            } else {
                DB::sql("SET @rank = 0");
                $centerRank = DB::sql("
                    SELECT ranked.rank FROM (
                        SELECT @rank := @rank+1 as `rank`, ts.character_id
                        FROM tournament_snapshots ts
                        JOIN `character` ch ON ch.id = ts.character_id
                        WHERE ts.tournament_id = {$tid}
                        ORDER BY GREATEST(0, CAST(ch.`{$sortCol}` AS SIGNED) - CAST(ts.`{$snapshotCol}` AS SIGNED)) DESC, ch.id ASC
                    ) ranked
                    WHERE ranked.character_id = {$player->character->id}
                ", [])->fetchColumn();
                $centerRank = intval($centerRank) ?: 1;
            }
        }

        $time = time();
        DB::sql("SET @rank = 0");
        $lb = DB::sql("
            SELECT ranked.* FROM (
                SELECT
                    @rank := @rank+1 as `rank`,
                    ch.id, ch.name, ch.guild_id, ch.gender, ch.level,
                    GREATEST(0, CAST(ch.`{$sortCol}` AS SIGNED) - CAST(ts.`{$snapshotCol}` AS SIGNED)) as `value`,
                    IF(({$time} - ch.ts_last_action) < 60, 1, 2) as online_status,
                    ch.league_group_id, ch.honor,
                    COALESCE(g.name, '') as guild_name,
                    g.emblem_background_shape, g.emblem_background_color,
                    g.emblem_background_border_color, g.emblem_icon_shape,
                    g.emblem_icon_color, g.emblem_icon_size
                FROM tournament_snapshots ts
                JOIN `character` ch ON ch.id = ts.character_id
                LEFT JOIN guild g ON g.id = ch.guild_id
                WHERE ts.tournament_id = {$tid}
                ORDER BY GREATEST(0, CAST(ch.`{$sortCol}` AS SIGNED) - CAST(ts.`{$snapshotCol}` AS SIGNED)) DESC, ch.id ASC
            ) ranked
            WHERE ranked.`rank` > {$centerRank} - 25
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach($lb as &$l){
            foreach($l as $k => &$c){
                if(is_numeric($c)) $c = intval($c);
            }
        }

        Core::req()->data = [
            'centered_rank' => $centerRank,
            'leaderboard_characters' => $lb,
            'max_characters' => $max_ch,
            'tournament_end_timestamp' => $tournament->ts_end,
        ];
    }
}
