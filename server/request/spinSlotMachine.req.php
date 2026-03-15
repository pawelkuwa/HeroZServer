<?php
namespace Request;

use Srv\Core;
use Srv\Config;
use Srv\DB;
use Cls\Bonus\SlotMachine;
use Cls\Bonus\ResourceType;

class spinSlotMachine{
    public function __request($player){
        if($player->getLVL() < Config::get('constants.slotmachine_min_level'))
            return Core::setError('');

        $lockName = 'slot_spin_'.$player->character->id;
        $lock = DB::sql("SELECT GET_LOCK('$lockName', 0) as locked")->fetch(\PDO::FETCH_ASSOC);
        if(!$lock || !$lock['locked'])
            return Core::setError('errSpinSlotmachineCharacterHasActiveSpin');

        try{
            if(SlotMachine::countCurrentSpins($player) > 0)
                return Core::setError('errSpinSlotmachineCharacterHasActiveSpin');
            if($player->character->slotmachine_spin_count >= Config::get('constants.slotmachine_max_daily_spins'))
                return Core::setError('errSpinSlotmachineDailyLimitReached');
            if(!$player->haveSlotmachineFreeSpin()){
                $cost = Config::get('constants.slotmachine_premium_currency_amount');
                if($player->getPremium() < $cost)
                    return Core::setError('errRemovePremiumCurrencyNotEnough');
                $player->givePremium(-$cost);
            }else{
                $cost = Config::get('constants.resource_free_slotmachine_spin_usage_amount');
                if($player->getUnusedResource(ResourceType::FreeSlotMachineSpin) >= $cost)
                    $player->giveUnusedResource(ResourceType::FreeSlotMachineSpin, -$cost);
                else{
                    $cost = Config::get('constants.resource_slotmachine_jeton_usage_amount');
                    if($player->getUnusedResource(ResourceType::SlotMachineJetons) >= $cost)
                        $player->giveUnusedResource(ResourceType::SlotMachineJetons, -$cost);
                }
            }

            $player->character->slotmachine_spin_count++;
            $machine = SlotMachine::spinSlotMachine($player);
            $player->character->current_slotmachine_spin = SlotMachine::countCurrentSpins($player);
            Core::req()->data = $machine->jsonSerialize();
            Core::req()->data['character'] = $player->character;
        }finally{
            DB::sql("SELECT RELEASE_LOCK('$lockName')");
        }
    }
}