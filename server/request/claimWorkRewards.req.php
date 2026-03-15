<?php
namespace Request;

use Srv\Core;

class claimWorkRewards{
    
    public function __request($player){
        if($player->character->active_work_id == 0)
            return Core::setError('errStartQuestActiveWorkFound');
        
        if($player->work->status != 4)
            return Core::setError('errUnknownStatus');
        
        $player->giveRewards($player->work->rewards);
        $player->incrementGoalStat('time_worked');
        $player->updateHerobookProgress(7);

        $player->work->remove();
        $player->character->active_work_id = 0;


        Core::req()->data = array(
            'character'=>$player->character,
            'work'=>array('id'=>$player->work->id, 'status'=>4)
        );

        if($player->inventory->sidekick_id)
            Core::req()->data['sidekick'] = $player->sidekicks;

        $herobookDrop = $player->rollHerobookItemDrop();
        if($herobookDrop)
            Core::req()->data['herobook_objectives'] = $player->getHerobookObjectives();
    }
}