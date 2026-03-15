<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Srv\Config;

class voteForGuildLeader{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');

        $guild = $player->guild;
        $leaderCharId = intval(getField('leader_character_id', FIELD_NUM));

        if($guild->pending_leader_vote_id == 0)
            return Core::setError('errVoteNoActiveVote');

        $stmt = DB::$connection->prepare("SELECT * FROM guild_leader_votes WHERE id = ? AND status = 1");
        $stmt->execute([$guild->pending_leader_vote_id]);
        $vote = $stmt->fetch(\PDO::FETCH_ASSOC);
        if(!$vote)
            return Core::setError('errVoteNoActiveVote');

        $duration = Config::get('constants.guild_leader_vote_duration', 24);
        if(time() > $vote['ts_creation'] + ($duration * 3600)){
            self::finishVote($vote, $guild);
            Core::req()->data = ['guild' => $guild];
            return;
        }

        $allowed = json_decode($vote['allowed_character_ids'], true);
        if(!in_array($player->character->id, $allowed))
            return Core::setError('errVoteNotAllowed');

        $results = json_decode($vote['vote_results'], true) ?: [];
        $results[(string)$player->character->id] = $leaderCharId;

        $stmt = DB::$connection->prepare("UPDATE guild_leader_votes SET vote_results = ? WHERE id = ?");
        $stmt->execute([json_encode($results), $vote['id']]);

        if(count($results) >= count($allowed))
            self::finishVote(array_merge($vote, ['vote_results' => json_encode($results)]), $guild);

        Core::req()->data = [
            'guild' => $guild,
            'guild_leader_vote' => \Request\initGuildLeaderVote::getVoteData($vote['id'])
        ];
    }

    private static function finishVote($vote, $guild){
        $results = json_decode($vote['vote_results'], true) ?: [];
        $allowed = json_decode($vote['allowed_character_ids'], true);
        $needed = ceil(count($allowed) / 2);

        $voteCounts = [];
        foreach($results as $voterId => $votedFor){
            if(!isset($voteCounts[$votedFor])) $voteCounts[$votedFor] = 0;
            $voteCounts[$votedFor]++;
        }

        arsort($voteCounts);
        $winnerId = array_key_first($voteCounts);
        $winnerVotes = $voteCounts[$winnerId] ?? 0;

        if($winnerId && $winnerId != $vote['current_leader_character_id'] && $winnerVotes >= $needed){
            DB::$connection->prepare("UPDATE `character` SET guild_rank = 3 WHERE id = ? AND guild_id = ?")->execute([$vote['current_leader_character_id'], $guild->id]);
            DB::$connection->prepare("UPDATE `character` SET guild_rank = 1 WHERE id = ? AND guild_id = ?")->execute([$winnerId, $guild->id]);
            $guild->leader_character_id = $winnerId;

            $stmt = DB::$connection->prepare("UPDATE guild_leader_votes SET status = 2, new_leader_character_id = ? WHERE id = ?");
            $stmt->execute([$winnerId, $vote['id']]);
        }else{
            DB::$connection->prepare("UPDATE guild_leader_votes SET status = 2 WHERE id = ?")->execute([$vote['id']]);
        }

        $guild->pending_leader_vote_id = 0;
    }
}
