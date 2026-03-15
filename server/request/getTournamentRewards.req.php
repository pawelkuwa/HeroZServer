<?php
namespace Request;

use Srv\Core;
use Schema\TournamentReward;

class getTournamentRewards{
    public function __request($player){
        if($player->character->pending_tournament_rewards <= 0)
            return Core::setError('errTournamentLocked');

        $reward = TournamentReward::find(function($q) use($player){
            $q->where('character_id', $player->character->id)->where('claimed', 0);
        });

        if(!$reward)
            return Core::setError('errTournamentLocked');

        Core::req()->data = [
            'tournament_rewards' => [
                'id' => $reward->id,
                'week' => $reward->week,
                'rewards' => $reward->rewards,
            ],
        ];
    }
}
