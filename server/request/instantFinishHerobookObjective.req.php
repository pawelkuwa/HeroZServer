<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\GameSettings;

class instantFinishHerobookObjective{

    public function __request($player){
        $id = intval(getField('id', FIELD_NUM));

        if(!$id)
            return Core::setError('errInvalidHerobookObjectiveId');

        $obj = DB::sql(
            "SELECT * FROM herobook_objectives WHERE id = " . $id .
            " AND character_id = " . intval($player->character->id) . " AND status = 1"
        )->fetch(\PDO::FETCH_ASSOC);

        if(!$obj)
            return Core::setError('errInvalidHerobookObjectiveId');

        // Calculate cost based on remaining progress
        $progress = $obj['max_value'] > 0 ? $obj['current_value'] / $obj['max_value'] : 0;
        $baseCost = $obj['duration_type'] == 1
            ? (int)GameSettings::getConstant('herobook_daily_objective_instant_finish_amount')
            : (int)GameSettings::getConstant('herobook_weekly_objective_instant_finish_amount');
        $cost = (int)ceil((1 - $progress) * $baseCost);
        $cost = max(1, $cost);

        if($player->getPremium() < $cost)
            return Core::setError('errRemovePremiumCurrencyNotEnough');

        $player->givePremium(-$cost);

        // Finish the objective
        DB::sql(
            "UPDATE herobook_objectives SET current_value = max_value, status = 2 WHERE id = " . $id
        );

        Core::req()->data = [
            'character' => $player->character,
            'herobook_objectives' => $player->getHerobookObjectives()
        ];
    }
}
