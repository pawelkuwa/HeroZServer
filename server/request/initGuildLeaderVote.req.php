<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class initGuildLeaderVote{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');

        $guild = $player->guild;

        if($guild->pending_leader_vote_id > 0)
            return Core::setError('errCreateVoteAlreadyRunning');

        $memberIds = [];
        foreach($guild->getMembers() as $m)
            $memberIds[] = $m->player->character->id;

        $stmt = DB::$connection->prepare("INSERT INTO guild_leader_votes (guild_id, status, ts_creation, initiator_character_id, current_leader_character_id, allowed_character_ids, vote_results) VALUES (?,1,?,?,?,?,?)");
        $stmt->execute([
            $guild->id,
            time(),
            $player->character->id,
            $guild->leader_character_id,
            json_encode($memberIds),
            '{}'
        ]);
        $voteId = DB::$connection->lastInsertId();
        $guild->pending_leader_vote_id = $voteId;

        Core::req()->data = [
            'guild' => $guild,
            'guild_leader_vote' => self::getVoteData($voteId)
        ];
    }

    public static function getVoteData($voteId){
        $stmt = DB::$connection->prepare("SELECT * FROM guild_leader_votes WHERE id = ?");
        $stmt->execute([$voteId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
