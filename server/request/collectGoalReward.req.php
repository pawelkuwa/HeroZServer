<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Srv\DB;
use Cls\Utils;
use Cls\GameSettings;

class collectGoalReward{

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
            return Core::setError('errGoalValueNotFound');

        // check not already collected
        $check = DB::sql(
            "SELECT id FROM collected_goals WHERE character_id = " . intval($player->character->id) .
            " AND goal_name = '" . addslashes($goalId) . "' AND milestone_value = " . intval($goalValue)
        )->fetch(\PDO::FETCH_ASSOC);
        if($check)
            return Core::setError('errGoalAlreadyCollected');

        // check player progress meets milestone
        $statsMap = $player->getGoalStatsMap();
        $currentValue = (int)($statsMap[$goal['lookup_column']] ?? 0);
        if($currentValue < $goalValue)
            return Core::setError('errGoalNotReached');

        // check required level
        if($goal['required_level'] > 0 && $player->getLVL() < $goal['required_level'])
            return Core::setError('errGoalLevelNotMet');

        // check dependency goal
        if(!empty($goal['required_goal'])){
            $dep = DB::sql(
                "SELECT id FROM collected_goals WHERE character_id = " . intval($player->character->id) .
                " AND goal_name = '" . addslashes($goal['required_goal']) . "' LIMIT 1"
            )->fetch(\PDO::FETCH_ASSOC);
            if(!$dep)
                return Core::setError('errGoalRequirementNotMet');
        }

        // give reward
        $reward = $goal['values'][$goalValue];
        $discardItem = getField('discard_item') == 'true';

        $this->giveGoalReward($player, $reward);

        // For item rewards: place pending item in inventory
        $itemSlot = null;
        $itemId = null;
        if($reward['reward_type'] == 5 && !$discardItem){
            $pending = DB::sql(
                "SELECT item_id FROM goal_pending_items WHERE character_id = " . intval($player->character->id) .
                " AND goal_identifier = '" . addslashes($goalId) . "' AND goal_value = " . intval($goalValue)
            )->fetch(\PDO::FETCH_ASSOC);

            if($pending){
                $itemSlot = $player->findEmptyInventorySlot();
                $itemId = (int)$pending['item_id'];
                if(!$itemSlot)
                    return Core::setError('errInventoryNoEmptySlot');
                $goalItem = $player->getItemById($itemId);
                $player->setItemInInventory($goalItem, $itemSlot);
                Utils::addItemToOwnedTemplates($player, $goalItem);
                Utils::addItemToPattern($player, $goalItem);
            }
        }

        // record collection
        DB::sql(
            "INSERT INTO collected_goals (character_id, goal_name, milestone_value, collected_at) VALUES (" .
            intval($player->character->id) . ", '" . addslashes($goalId) . "', " . intval($goalValue) . ", " . time() . ")"
        );

        // Clean up pending item
        DB::sql(
            "DELETE FROM goal_pending_items WHERE character_id = " . intval($player->character->id) .
            " AND goal_identifier = '" . addslashes($goalId) . "' AND goal_value = " . intval($goalValue)
        );

        $collected = $player->getCollectedGoals();
        $goalVals = $player->getGoalValues();

        Core::req()->data = [
            'character' => $player->character,
            'collected_goals' => $collected,
            'current_goal_values' => $goalVals
        ];

        if($itemSlot && $itemId)
            Core::req()->data['inventory'] = ['id' => $player->inventory->id, $itemSlot => $itemId];

        if($player->inventory->sidekick_id)
            Core::req()->data['sidekick'] = $player->sidekicks;
    }

    private function giveGoalReward($player, $reward){
        // SWF GoalRewardType: 1=GameCurrency, 2=PremiumCurrency, 3=StatPoint, 4=Xp,
        // 5=Item, 6=Training, 7=QuestEnergy, 8=Booster, 9=Sidekick
        $type = $reward['reward_type'];
        $factor = $reward['reward_factor'] ?? 1;
        $level = $reward['estimated_level'] ?? $player->getLVL();

        switch($type){
            case 1: // GameCurrency (gold)
                $cpt = \Cls\Utils::coinsPerTime($level);
                $time = GameSettings::getConstant('goal_reward_game_currency_time');
                $pctBase = GameSettings::getConstant('goal_reward_game_currency_percentage_base');
                $coins = round($cpt * $pctBase * $factor * $time);
                $coins += pow($level, GameSettings::getConstant('goal_reward_game_currency_exp'));
                $coins = round($coins);
                $player->giveMoney(max($coins, 1));
                break;

            case 2: // PremiumCurrency (donuts)
                $donuts = GameSettings::getConstant('goal_reward_premium_currency_base') * round($factor);
                $player->givePremium($donuts);
                break;

            case 3: // StatPoint
                $points = GameSettings::getConstant('goal_reward_stat_points_base') * round($factor);
                $player->character->stat_points_available += $points;
                break;

            case 4: // Xp
                $levels = Config::get('constants.levels');
                $xpForLevel = isset($levels[$level]) ? (int)$levels[$level]['xp'] : 0;
                $xpNext = isset($levels[$level + 1]) ? (int)$levels[$level + 1]['xp'] : $xpForLevel * 2;
                $xpNeeded = $xpNext - $xpForLevel;
                $pct = GameSettings::getConstant('goal_reward_xp_percentage_base') * $factor;
                $xp = round($xpNeeded * $pct);
                $player->giveExp(max($xp, 1));
                break;

            case 5: // Item (already created by getGoalItemReward, nothing to do here)
                break;

            case 6: // Training Sessions
                $amount = GameSettings::getConstant('goal_reward_training_base') * round($factor);
                $player->character->training_count += max($amount, 1);
                break;

            case 7: // Quest Energy
                $amount = GameSettings::getConstant('goal_reward_energy_base') * round($factor);
                $player->character->quest_energy += max($amount, 1);
                break;

            case 8: // Booster
                $boosterId = $reward['reward_identifier'] ?? '';
                if($boosterId){
                    $booster = Config::get("constants.boosters.$boosterId", false);
                    if($booster){
                        $types = ["quest", "stats", "work"];
                        $bType = min(max((int)$booster['type'], 1), 3);
                        $actId = 'active_'.$types[$bType-1].'_booster_id';
                        $tsCol = 'ts_active_'.$types[$bType-1].'_boost_expires';
                        $addTime = time();
                        if($player->character->{$tsCol} > time())
                            $addTime = $player->character->{$tsCol};
                        $player->character->{$tsCol} = $addTime + $booster['duration'];
                        $player->character->{$actId} = $boosterId;
                    }
                }
                break;
        }
    }
}
