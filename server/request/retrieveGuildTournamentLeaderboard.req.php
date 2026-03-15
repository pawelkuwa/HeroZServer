<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Schema\Tournament;
use PDO;

class retrieveGuildTournamentLeaderboard{
    public function __request($player){
        $type = intval(getField('type', FIELD_NUM));
        if($type < 1 || $type > 2)
            return Core::setError('errTournamentLocked');

        $tournament = Tournament::find(function($q){ $q->where('status', 1); });
        if(!$tournament)
            return Core::setError('errTournamentLocked');

        $guild_name = getField('guild_name', FIELD_ALNUM, FALSE);
        $sort_rank = intval(getField('rank', FIELD_NUM, FALSE));
        $tid = $tournament->id;

        $max_guilds = DB::sql("
            SELECT COUNT(DISTINCT ts.guild_id)
            FROM tournament_snapshots ts
            WHERE ts.tournament_id = {$tid} AND ts.guild_id > 0
        ")->fetchColumn();
        $max_guilds = intval($max_guilds);

        if($max_guilds == 0)
            return Core::setError('errRetrieveGuildLeaderboardEmpty');

        if($sort_rank < 0 || $sort_rank > $max_guilds)
            return Core::setError('errRetrieveGuildLeaderboardInvalidRank');

        if($sort_rank){
            $centerRank = $sort_rank;
        } else {
            if($guild_name){
                DB::sql("SET @rank = 0");
                $centerRank = DB::sql("
                    SELECT ranked.rank FROM (
                        SELECT @rank := @rank+1 as `rank`, g.id, g.name,
                            GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) as delta
                        FROM tournament_snapshots ts
                        JOIN guild g ON g.id = ts.guild_id
                        WHERE ts.tournament_id = {$tid} AND ts.guild_id > 0
                        GROUP BY ts.guild_id
                        ORDER BY delta DESC, g.id ASC
                    ) ranked
                    WHERE ranked.name LIKE ?
                    LIMIT 1
                ", ["%{$guild_name}%"])->fetchColumn();
                $centerRank = intval($centerRank);
                if(!$centerRank)
                    return Core::setError('errRetrieveGuildLeaderboardInvalidGuild');
            } else {
                $gid = $player->character->guild_id;
                if($gid == 0) $gid = -1;
                DB::sql("SET @rank = 0");
                $centerRank = DB::sql("
                    SELECT ranked.rank FROM (
                        SELECT @rank := @rank+1 as `rank`, g.id,
                            GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) as delta
                        FROM tournament_snapshots ts
                        JOIN guild g ON g.id = ts.guild_id
                        WHERE ts.tournament_id = {$tid} AND ts.guild_id > 0
                        GROUP BY ts.guild_id
                        ORDER BY delta DESC, g.id ASC
                    ) ranked
                    WHERE ranked.id = {$gid}
                ")->fetchColumn();
                $centerRank = intval($centerRank) ?: 1;
            }
        }

        DB::sql("SET @rank = 0");
        $lb = DB::sql("
            SELECT ranked.* FROM (
                SELECT
                    @rank := @rank+1 as `r`,
                    g.id, g.name as `n`,
                    GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) as `v`,
                    g.emblem_background_shape as ebs, g.emblem_background_color as ebc,
                    g.emblem_background_border_color as ebbc, g.emblem_icon_shape as eis,
                    g.emblem_icon_color as eic, g.emblem_icon_size as eiz
                FROM tournament_snapshots ts
                JOIN guild g ON g.id = ts.guild_id
                WHERE ts.tournament_id = {$tid} AND ts.guild_id > 0
                GROUP BY ts.guild_id
                ORDER BY GREATEST(0, CAST(g.honor AS SIGNED) - CAST(MIN(ts.guild_honor_start) AS SIGNED)) DESC, g.id ASC
            ) ranked
            WHERE ranked.`r` > {$centerRank} - 25
            LIMIT 50
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach($lb as &$l){
            foreach($l as $k => &$c){
                if(is_numeric($c)) $c = intval($c);
            }
        }

        Core::req()->data = [
            'centered_rank' => $centerRank,
            'leaderboard_guilds' => $lb,
            'max_guilds' => $max_guilds,
            'tournament_end_timestamp' => $tournament->ts_end,
        ];
    }
}
