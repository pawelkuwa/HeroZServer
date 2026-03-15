<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\GameSettings;
use Cls\Utils;
use Cls\Utils\Item;
use Cls\Utils\ItemsList;

class getGoalItemReward{

    public function __request($player){
        $goalId = getField('identifier');
        $goalValue = intval(getField('value', FIELD_NUM));

        if(!$goalId || !$goalValue)
            return Core::setError('errGoalNotFound');

        $goals = GameSettings::getConstant('goals');
        if(!isset($goals[$goalId]))
            return Core::setError('errGoalNotFound');

        $goal = $goals[$goalId];
        if(!$goal['active'] || !isset($goal['values'][$goalValue]))
            return Core::setError('errGoalNotFound');

        $reward = $goal['values'][$goalValue];

        if($reward['reward_type'] != 5)
            return Core::setError('errGoalNotFound');

        // Check if item was already generated for this goal
        $existing = DB::sql(
            "SELECT item_id FROM goal_pending_items WHERE character_id = " . intval($player->character->id) .
            " AND goal_identifier = '" . addslashes($goalId) . "' AND goal_value = " . intval($goalValue)
        )->fetch(\PDO::FETCH_ASSOC);

        if($existing){
            $item = $player->getItemById((int)$existing['item_id']);
            if($item){
                Core::req()->data = [
                    'character' => $player->character,
                    'item' => $item
                ];
                return;
            }
            DB::sql("DELETE FROM goal_pending_items WHERE character_id = " . intval($player->character->id) .
                " AND goal_identifier = '" . addslashes($goalId) . "' AND goal_value = " . intval($goalValue));
        }

        // Generate new item (preview only, NOT placed in inventory yet)
        $level = $reward['estimated_level'] ?? $player->getLVL();
        $type = mt_rand(1, 7);
        $itemPool = ItemsList::$ITEMS[Item::$TYPE[$type]] ?? [];

        if(empty($itemPool)){
            $type = 6;
            $itemPool = ItemsList::$ITEMS['weapon'];
        }

        $itemData = $itemPool[mt_rand(0, count($itemPool) - 1)];
        $item = $player->createItem($itemData);
        Utils::randomiseItem($item, max($level, 1));

        // Store pending reference (item exists but NOT in inventory)
        DB::sql(
            "INSERT INTO goal_pending_items (character_id, goal_identifier, goal_value, item_id, created_at) VALUES (" .
            intval($player->character->id) . ", '" . addslashes($goalId) . "', " . intval($goalValue) . ", " .
            intval($item->id) . ", " . time() . ")"
        );

        Core::req()->data = [
            'character' => $player->character,
            'item' => $item
        ];
    }
}
