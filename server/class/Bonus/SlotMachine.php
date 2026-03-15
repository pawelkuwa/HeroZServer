<?php
namespace Cls\Bonus;

use Schema\SlotMachines;
use Cls\Reward;
use Schema\Character;
use Srv\Config;
use Cls\Utils;
use Cls\Utils\Item;
use Cls\Utils\ItemsList;
use Cls\GameSettings;

class SlotMachine{
    
    // Symbols: 1=Coins, 2=Item, 3=Booster, 4=Sidekick, 5=StatPoints, 6=Xp, 7=Energy, 8=Training
    private static $BOOSTER_TIERS = [
        1 => ['booster_quest1', 'booster_stats1', 'booster_work1'],
        2 => ['booster_quest2', 'booster_stats2', 'booster_work2'],
        3 => ['booster_quest3', 'booster_stats3', 'booster_work3'],
    ];
    private static $BOOSTER_DAYS = [1 => 2, 2 => 4, 3 => 7];

    public static function spinSlotMachine($player){
        $slot1 = mt_rand(1,8);
        $slot2 = mt_rand(1,8);
        $slot3 = mt_rand(1,8);
        $won = false;

        if($slot1 == $slot2 && $slot2 == $slot3){
            $won = true;
            $quality = 3;
            $matchedSymbol = $slot1;
        }else if($slot1 == $slot2 || $slot2 == $slot3 || $slot1 == $slot3){
            $quality = 2;
            if($slot1 == $slot2) $matchedSymbol = $slot1;
            else if($slot2 == $slot3) $matchedSymbol = $slot2;
            else $matchedSymbol = $slot1;
        }else{
            $quality = 1;
            $matchedSymbol = (random() < 0.5) ? 1 : 6;
        }

        $level = $player->getLVL();
        $rewardJson = null;

        switch($matchedSymbol){
            case 1: // Coins
                $reward = new Reward();
                $reward->coins($level * Config::get('constants.slotmachine_coin_reward_base_time') * $quality * random(0.05, 0.1));
                $rewardJson = $reward->toJSON();
                break;
            case 2: // Item
                $itemData = self::generateSlotItem($player, $quality);
                if($itemData){
                    $reward = new Reward();
                    $reward->item($itemData->id);
                    $rewardJson = $reward->toJSON();
                }else{
                    $reward = new Reward();
                    $reward->coins($level * Config::get('constants.slotmachine_coin_reward_base_time') * $quality * random(0.08, 0.15));
                    $rewardJson = $reward->toJSON();
                    $matchedSymbol = 1;
                }
                break;
            case 4: // Sidekick — fallback to coins (SWF has no sidekick reward dialog)
                $reward = new Reward();
                $reward->coins($level * Config::get('constants.slotmachine_coin_reward_base_time') * $quality * random(0.08, 0.15));
                $rewardJson = $reward->toJSON();
                $matchedSymbol = 1;
                break;
            case 3: // Booster
                $tier = min($quality, 3);
                $pool = self::$BOOSTER_TIERS[$tier];
                $boosterId = $pool[mt_rand(0, count($pool) - 1)];
                $rewardJson = json_encode(['booster' => [
                    'booster_id' => $boosterId,
                    'days' => self::$BOOSTER_DAYS[$tier],
                ]]);
                break;
            case 5: // Stat Points
                $reward = new Reward();
                $reward->statPoints(max(1, $quality * mt_rand(1, 2)));
                $rewardJson = $reward->toJSON();
                break;
            case 6: // XP
                $reward = new Reward();
                $reward->xp($level * Config::get('constants.slotmachine_xp_reward_base_time') * $quality * random(0.05, 0.07));
                $rewardJson = $reward->toJSON();
                break;
            case 7: // Quest Energy
                $reward = new Reward();
                $reward->questEnergy($quality * mt_rand(1, 3));
                $rewardJson = $reward->toJSON();
                break;
            case 8: // Training Sessions
                $reward = new Reward();
                $reward->trainingSessions($quality * mt_rand(1, 2));
                $rewardJson = $reward->toJSON();
                break;
        }

        $machine = new SlotMachines([
            'character_id'=>$player->character->id,
            'slotmachine_reward_quality'=>$quality,
            'slotmachine_slot1'=>$slot1,
            'slotmachine_slot2'=>$slot2,
            'slotmachine_slot3'=>$slot3,
            'reward'=>$rewardJson,
            'slot'=>$matchedSymbol,
            'won'=>$won,
            'timestamp'=>time()
        ]);
        $machine->save();
        return $machine;
    }
    
    private static function generateSlotItem($player, $quality){
        $lvl = $player->getLVL();
        $type = mt_rand(1, 7);
        $typeName = Item::$TYPE[$type];
        $itemPool = ItemsList::$ITEMS[$typeName] ?? [];
        if(empty($itemPool)) return null;

        $itemQuality = min($quality, 3);
        $candidates = array_filter($itemPool, function($it) use($itemQuality, $lvl){
            return $it['quality'] == $itemQuality && $it['required_level'] <= $lvl;
        });
        if(empty($candidates)){
            $candidates = array_filter($itemPool, function($it) use($lvl){
                return $it['required_level'] <= $lvl;
            });
        }
        if(empty($candidates)) return null;

        $candidates = array_values($candidates);
        $picked = $candidates[mt_rand(0, count($candidates) - 1)];

        $newItem = $player->createItem($picked);
        $newItem = Utils::randomiseItem($newItem, $lvl);
        if($type == (Item::$TYPE_ID['weapon'] ?? 6))
            $newItem->stat_weapon_damage = round($newItem->item_level * GameSettings::getConstant('item_weapon_damage_factor'));
        else
            $newItem->stat_weapon_damage = 0;

        Utils::addItemToOwnedTemplates($player, $newItem);
        Utils::addItemToPattern($player, $newItem);

        return $newItem;
    }

    public static function countCurrentSpins($player){
        return SlotMachines::count(function($q)use($player){$q->where('character_id',$player->character->id)->where('history',0);});
    }
    
    public static function findCurrentReward($player){
        return SlotMachines::find(function($q)use($player){$q->where('character_id',$player->character->id)->where('history',0)->orderBy('timestamp');});
    }

    public static function getLastWins(){
        $returned_data = [];

        $history = SlotMachines::findAll(function($q){
            $q->where('history',1)->where('won',1)->orderBy('timestamp')->limit(10);
        });
  
            foreach ($history as $person) {
                $chid = $person->character_id;

                $char = Character::find(function($q) use($chid){ 
                    $q->where('user_id',$chid); 
                });

                $slot = $person->slot + 400; //+400 cuz playata
                array_push($returned_data, [
                    'character_gender'=>$char->gender,
                    'character_level'=>$char->level,
                    'character_name'=>$char->name,
                    'character_id'=>$char->user_id,
                    'type'=>$slot,
                    'value1'=>current(json_decode($person->reward, true)),
                    'timestamp'=>time()
                ]);
            }

        return $returned_data;
    }
}