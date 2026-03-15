<?php
namespace Request;

use Srv\Core;
use Schema\TournamentReward;

class claimTournamentRewards{
    public function __request($player){
        if($player->character->pending_tournament_rewards <= 0)
            return Core::setError('errClaimTournamentRewardsNoRewards');

        $reward = TournamentReward::find(function($q) use($player){
            $q->where('character_id', $player->character->id)->where('claimed', 0);
        });

        if(!$reward)
            return Core::setError('errClaimTournamentRewardsNoRewards');

        $rewards = json_decode($reward->rewards, true);
        $totalDonuts = 0;
        $totalGuildPremium = 0;

        for($t = 1; $t <= 5; $t++){
            if(!isset($rewards[$t]) || !isset($rewards[$t]['reward']))
                continue;
            $type = $rewards[$t]['reward']['type'] ?? 0;
            $amount = $rewards[$t]['reward']['amount'] ?? 0;
            if($amount <= 0) continue;

            switch($type){
                case 1:
                    $totalDonuts += $amount;
                    break;
                case 2:
                    $totalGuildPremium += $amount;
                    break;
            }
        }

        if($totalDonuts > 0)
            $player->givePremium($totalDonuts);

        if($totalGuildPremium > 0 && $player->character->guild_id > 0)
            $player->guild->givePremium($totalGuildPremium);

        $reward->claimed = 1;
        $player->character->pending_tournament_rewards -= 1;
        if($player->character->pending_tournament_rewards < 0)
            $player->character->pending_tournament_rewards = 0;

        $player->incrementGoalStat('tournament_attended');

        $hasTop10 = false;
        $hasTop3 = false;
        for($t = 1; $t <= 5; $t++){
            if(isset($rewards[$t]) && isset($rewards[$t]['rank'])){
                $rank = $rewards[$t]['rank'];
                if($rank > 0 && $rank <= 10) $hasTop10 = true;
                if($rank > 0 && $rank <= 3) $hasTop3 = true;
            }
        }
        if($hasTop10) $player->incrementGoalStat('tournament_top10_reached');
        if($hasTop3) $player->incrementGoalStat('tournament_top3_reached');

        Core::req()->data = [
            'user' => $player->user,
            'character' => $player->character,
        ];
        if($player->character->guild_id > 0)
            Core::req()->data['guild'] = $player->guild;
    }
}
