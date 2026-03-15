<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class collectHerobookReward{

    public function __request($player){
        $id = intval(getField('id', FIELD_NUM));
        $discardItem = getField('discard_item') == 'true';

        if(!$id)
            return Core::setError('errInvalidHerobookObjectiveId');

        $obj = DB::sql(
            "SELECT * FROM herobook_objectives WHERE id = " . $id .
            " AND character_id = " . intval($player->character->id) . " AND status = 2"
        )->fetch(\PDO::FETCH_ASSOC);

        if(!$obj)
            return Core::setError('errInvalidHerobookObjectiveId');

        $rewards = json_decode($obj['rewards'], true);

        // Give rewards
        $player->giveRewards($rewards);
        $player->character->herobook_objectives_finished++;
        $player->incrementGoalStat('herobook_objectives_finished');

        // Mark as collected
        DB::sql("UPDATE herobook_objectives SET status = 3 WHERE id = " . $id);

        Core::req()->data = [
            'character' => $player->character,
            'herobook_objectives' => $player->getHerobookObjectives()
        ];

        if($player->inventory->sidekick_id)
            Core::req()->data['sidekick'] = $player->sidekicks;
    }
}
