<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Srv\Socket;
use Cls\Bonus\SlotMachine;

class applySlotMachineReward{
    public function __request($player){
        $machine = SlotMachine::findCurrentReward($player);
        if(!$machine)
            return Core::setError('errApplySlotmachineRewardCharacterHasNoActiveSpin');

        $rewardData = json_decode($machine->reward, true);

        $invUpdate = null;

        if(isset($rewardData['booster'])){
            $boosterId = $rewardData['booster']['booster_id'];
            $booster = Config::get("constants.boosters.$boosterId", false);
            if($booster){
                $types = ["quest", "stats", "work"];
                $actId = 'active_'.$types[$booster['type']-1].'_booster_id';
                $tsCol = 'ts_active_'.$types[$booster['type']-1].'_boost_expires';
                $addTime = max(time(), $player->character->{$tsCol});
                $player->character->{$tsCol} = $addTime + $booster['duration'];
                $player->character->{$actId} = $boosterId;
                $player->calculateStats();
            }
        }else if(isset($rewardData['item']) && $rewardData['item'] > 0){
            $slot = $player->findEmptyInventorySlot();
            if(!$slot)
                return Core::setError('errInventoryNoEmptySlot');
            $item = $player->getItemById($rewardData['item']);
            if($item){
                $player->setItemInInventory($item, $slot);
                $invUpdate = ['id' => $player->inventory->id, $slot => $item->id];
            }
        }else{
            $player->giveRewards($machine->reward);
        }

        $machine->history = 1;
        $player->character->current_slotmachine_spin = SlotMachine::countCurrentSpins($player);

        if($machine->won){
            if(isset($rewardData['booster']))
                $chatValue1 = $rewardData['booster']['booster_id'];
            else if(isset($rewardData['item']))
                $chatValue1 = $rewardData['item'];
            else
                $chatValue1 = current($rewardData);

            Socket::syncSlotmachineChat([
                'character_gender' => $player->character->gender,
                'character_level' => $player->character->level,
                'character_name' => $player->character->name,
                'character_id' => $player->character->user_id,
                'type' => $machine->slot + 400,
                'value1' => $chatValue1,
                'timestamp' => time(),
            ]);
        }

        $data = ['character' => $player->character];
        if($invUpdate)
            $data['inventory'] = $invUpdate;
        if(isset($rewardData['item']) && $rewardData['item'] > 0)
            $data['items'] = $player->items;
        Core::req()->data = $data;
    }
}