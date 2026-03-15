<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\GameSettings;

class renewHerobookObjective{

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

        // Cost increases with each renewal today
        $renewAmount = (int)GameSettings::getConstant('herobook_objective_renew_amount');
        $cost = ($player->character->herobook_objectives_renewed_today + 1) * $renewAmount;

        if($player->getPremium() < $cost)
            return Core::setError('errRemovePremiumCurrencyNotEnough');

        $player->givePremium(-$cost);
        $player->character->herobook_objectives_renewed_today++;

        // Abort old objective
        DB::sql("UPDATE herobook_objectives SET status = 4 WHERE id = " . $id);

        // Create replacement with same duration type and index
        $durationType = (int)$obj['duration_type'];
        $oldIndex = (int)$obj['objective_index'];
        $oldIdentifier = $obj['identifier'];

        $objectives = GameSettings::getConstant('herobook_objectives');
        $level = $player->getLVL();

        // Filter eligible objectives
        $eligible = [];
        foreach($objectives as $oid => $odata){
            if($odata['min_level'] > $level) continue;
            if($durationType == 1 && $odata['daily'] <= 0) continue;
            if($durationType == 2 && $odata['weekly'] <= 0) continue;
            if($oid == $oldIdentifier) continue;
            $eligible[$oid] = $odata;
        }

        // Get current active identifiers to avoid duplicates
        $existing = DB::sql(
            "SELECT identifier FROM herobook_objectives WHERE character_id = " . intval($player->character->id) .
            " AND status IN (1,2) AND ts_end >= " . time()
        )->fetchAll(\PDO::FETCH_COLUMN);
        $existingMap = array_flip($existing);

        $keys = array_keys($eligible);
        shuffle($keys);

        $tsEnd = $player->getHerobookExpiry($durationType);

        foreach($keys as $newId){
            if(isset($existingMap[$newId])) continue;
            $odata = $eligible[$newId];
            $baseValue = $durationType == 1 ? $odata['daily'] : $odata['weekly'];
            $maxValue = $player->calculateHerobookMaxValue($baseValue, $odata['variance'], $odata['scaling'], $level);
            $rewards = $player->rollHerobookReward($durationType, $level);

            DB::sql(
                "INSERT INTO herobook_objectives (character_id, identifier, type, duration_type, status, current_value, max_value, ts_end, objective_index, rewards) VALUES (" .
                intval($player->character->id) . ", '" . addslashes($newId) . "', " . intval($odata['type']) . ", " .
                $durationType . ", 1, 0, " . $maxValue . ", " . $tsEnd . ", " . $oldIndex . ", '" . addslashes($rewards) . "')"
            );
            break;
        }

        $herobookData = $player->getHerobookObjectives();
        $herobookData->newObjectivesCreated = true;

        Core::req()->data = [
            'character' => $player->character,
            'herobook_objectives' => $herobookData
        ];
    }
}
