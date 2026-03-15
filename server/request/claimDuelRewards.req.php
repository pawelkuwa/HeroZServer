<?php
namespace Request;

use Srv\Core;

class claimDuelRewards{
    
    public function __request($player){
        if($player->character->active_duel_id == 0)
            return Core::setError('errClaimDuelRewardsNoActiveDuel');
            
        $player->giveRewards($player->duel->character_a_rewards);
        $player->incrementGoalStat('duels_completed');
        if($player->battle->winner == 'a'){
            $player->incrementGoalStat('duels_won');
            $wonInRow = $player->incrementGoalStat('duels_won_in_row');
            $best = $player->getGoalStats()['duels_won_in_row_best'] ?? 0;
            if($wonInRow > $best) $player->setGoalStat('duels_won_in_row_best', $wonInRow);
        } else {
            $player->incrementGoalStat('duels_lost');
            $player->setGoalStat('duels_won_in_row', 0);
        }
        $player->updateHerobookProgress(11);

        $player->character->active_duel_id = 0;
        $player->duel->character_a_status = 3;
        
        if($player->inventory->sidekick_id)
            Core::req()->data['sidekick'] = $player->sidekicks;

        Core::req()->data = array(
            "user" => array(),
			"character" => $player->character,
			"duel" => [
				"id" => $player->duel->id,
				"character_a_status" => 3
			]
        );
        
        //TODO: remove missile item
        //if($player->getItemFromSlot('missiles_item_id') != null)
        //    Core::req()->data += array("items"=>array($player->getItemFromSlot('missiles_item_id')));

        $herobookDrop = $player->rollHerobookItemDrop();
        if($herobookDrop)
            Core::req()->data['herobook_objectives'] = $player->getHerobookObjectives();
    }
}