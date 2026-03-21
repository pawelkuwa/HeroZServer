<?php
namespace Cls;

use Srv\Core;
use Srv\DB;
use Srv\Config;
use Cls\Utils\Item;
use Cls\Entity;
use Cls\Guild;
use Schema\User;
use Schema\Character;
use Schema\Inventory;
use Schema\BankInventory;
use Schema\Quests;
use Schema\Dungeons;
use Schema\DungeonQuests;
use Schema\Sidekicks;
use Schema\Items;
use Schema\Work;
use Schema\Training;
use Schema\Battle;
use Schema\Duel;
use Schema\Messages;
use Schema\LeagueFight AS DBLeagueFight;
use Cls\GameSettings;
use Cls\Bonus\SlotMachine;
use Cls\Bonus\ResourceType;

class Player extends Entity{

    public $user = null;
    public $character = null;
    public $inventory = null;
    public $bankinv = null;
    public $work = null;
    public $training = null;
	public $dbleaguefight = null;
    public $duel = null;
    public $battle = null;
    public $guild = null;
    public $sidekicks = null;
    //
    public $items = [];
    public $quests = [];
    public $dungeons = [];
    public $dungeon_quests = [];
    public $battles = [];
    //
    public $ts_before_action = 0;
    
    public function loadPlayer(){
        Core::$PLAYER = $this;
        if(!$this->character)
            $this->character = Character::find(function($q){ $q->where('user_id', $this->user->id); });
        if($this->character){
            if($this->character->guild_id != 0 && !$this->guild){
                $this->guild = Guild::find(function($q){ $q->where('id',$this->character->guild_id); });
                $this->guild->loadGuild();
                if(($finishedAttack = $this->guild->getFinishedAttack()) != null)
                    $this->character->finished_guild_battle_attack_id = $finishedAttack->battle->id;
                if(($finishedDefense = $this->guild->getFinishedDefense()) != null)
                    $this->character->finished_guild_battle_defense_id = $finishedDefense->battle->id;
            }
            $this->calculateLVL();
            $this->quests = Quests::findAll(function($q){ $q->where('character_id', $this->character->id)->where('status','<',5); });
            $this->updateQuestsPool();
            $this->items = Items::findAll(function($q){ $q->where('character_id', $this->character->id); });
            $this->dungeons = Dungeons::findAll(function($q) { $q->where('character_id', $this->character->id); });
            $this->dungeon_quests = DungeonQuests::findAll(function($q) { $q->where('character_id', $this->character->id); });
            $this->work = Work::find(function($q){ $q->where('character_id', $this->character->id)->where('status','<',5); });
            if($this->work)
                $this->character->active_work_id = $this->work->id;
            $this->training = Training::find(function($q){ $q->where('character_id', $this->character->id)->where('status','<',5); });
            if($this->training)
                $this->character->active_training_id = $this->training->id;
            $this->duel = Duel::find(function($q){ $q->where('character_a_id', $this->character->id)->where('character_a_status','<',3); });
            if($this->duel){
                $this->character->active_duel_id = $this->duel->id;
                $this->battle = Battle::find(function($q){ $q->where('id',$this->duel->battle_id); });
                $this->battles[] = $this->battle;
            }
            $this->league_fight = DBLeagueFight::find(function($q){ $q->where('character_a_id', $this->character->id)->where('character_a_status','<',3); });
            if($this->league_fight){
                $this->character->active_league_fight_id = $this->league_fight->id;
                $this->battle = Battle::find(function($q){ $q->where('id',$this->league_fight->battle_id); });
                $this->battles[] = $this->battle;
            }
            if(($battleQuest = $this->getBattleQuest()) != null){
                $battleId = $battleQuest->fight_battle_id;
                $this->battle = Battle::find(function($q)use($battleId){ $q->where('id',$battleId); });
                $this->battles[] = $this->battle;
            }
            if(($battleDungeon = $this->getBattleDungeons()) != null){
                $battleId = $battleDungeon->battle_id;
                $this->battle = Battle::find(function($q)use($battleId){ $q->where('id',$battleId); });
                $this->battles[] = $this->battle;
            }
            if(!$this->inventory)
                $this->inventory = Inventory::find(function($q){ $q->where('character_id', $this->character->id); });
            if(!$this->bankinv)
                $this->bankinv = BankInventory::find(function($q){ $q->where('character_id', $this->character->id); });
            if($this->inventory->sidekick_id > 0) $this->sidekicks = Sidekicks::find(function($q) { $q->where('id', $this->inventory->sidekick_id); });
            //TODO: Check event timestamp (event exists)
            $this->character->current_slotmachine_spin = SlotMachine::countCurrentSpins($this);
        }
        if($this->character){
            $this->ts_before_action = $this->character->ts_last_action;
            if(Utils::isNotToday($this->character->ts_last_action))
                $this->regenerateSometime();
            $this->character->ts_last_action = time();
            $this->character->online_status = time() < $this->character->ts_last_action + 60? 1 : 2;
            $this->refreshDuelStamina();
			$this->refreshLeagueStamina();
            if($this->character->ts_active_sense_boost_expires < time())
                $this->character->ts_active_sense_boost_expires = 0;
            $this->calculateStats();
            $this->calculateEntity();
        }
    }
    
    //LOADING//
    public function loadForDuel(){
        if(!$this->character)
            $this->character = Character::find(function($q){ $q->where('user_id', $this->user->id); });
        if($this->character->guild_id != 0 && !$this->guild)
            $this->guild = Guild::find(function($q){ $q->where('id',$this->character->guild_id); });
        $this->character->online_status = time() < $this->character->ts_last_action + 60? 1 : 2;
        $this->calculateLVL();
        $this->inventory = Inventory::find(function($q){ $q->where('character_id', $this->character->id); });
        $this->items = Items::findAll(function($q){ $q->where('character_id', $this->character->id); });
        if($this->inventory->sidekick_id > 0) $this->sidekicks = Sidekicks::find(function($q) { $q->where('id', $this->inventory->sidekick_id); });
        //var_dump($this->sidekicks);
        //var_dump($this->inventory);
        if($this->character->guild_id != 0)
            $this->playerLoadFightGuild();
        $this->calculateStats();
        $this->calculateEntity();
    }
    
    public function loadForGuild(){
        $this->character->online_status = time() < $this->character->ts_last_action + 60? 1 : 2;
        $this->calculateLVL();
        $this->inventory = Inventory::find(function($q){ $q->where('character_id', $this->character->id); });
        $this->items = Items::findAll(function($q){ $q->where('character_id', $this->character->id); });
        if($this->character->guild_id != 0)
            $this->playerLoadFightGuild();
        $this->calculateStats();
        $this->calculateEntity();
    }
    
    public function loadForCharacterView(){
        if(!$this->guild && $this->character->guild_id != 0)
            $this->guild = Guild::find(function($q){ $q->where('id',$this->character->guild_id); });
        $this->items = Items::findAll(function($q){ $q->where('character_id', $this->character->id); });
        if(!$this->inventory)
            $this->inventory = Inventory::find(function($q){ $q->where('character_id', $this->character->id); });
        if(!$this->sidekicks)
            $this->sidekicks = Sidekicks::find(function($q) { $q->where('id', $this->inventory->sidekick_id); });
        $this->calculateStats();
    }
    
    private function playerLoadFightGuild(){
        $gid = $this->character->guild_id;
        if(isset(Core::$GUILDS[$gid]))
            $this->guild = Core::$GUILDS[$gid];
        else{
            $this->guild = Guild::find(function($q){ $q->where('id',$this->character->guild_id); });
            $this->guild->loadGuildForBattle();
        }
    }
    //END LOADING//
    
    public function getUnreadedMessagesCount(){
        return Messages::count(function($q){
            $q->where('character_to_ids','LIKE',"%;{$this->character->id};%");
            $q->where('readed',0);
        });
    }
	
    public function getMissedDuels(){
        return Duel::count(function($q){
            $q->where('character_b_id', $this->character->id);
            $q->where('character_b_status', 1);
			$q->where('unread', 'true');
        });
    }	
	
    public function getMissedLeagueFights(){
        return DBLeagueFight::count(function($q){
            $q->where('character_b_id', $this->character->id);
            $q->where('character_b_status', 1);
			$q->where('unread', 'true');
        });
    }	
    
    public function calculateEntity(){
        $this->hitpoints = $this->character->stat_total_stamina * Config::get('constants.battle_hp_scale');
        $this->level = $this->character->level;
        $this->stamina = $this->character->stat_total_stamina;
        $this->total_stamina = $this->stamina;
        $this->strength = $this->character->stat_total_strength;
        $this->criticalrating = $this->character->stat_total_critical_rating;
        $this->dodgerating = $this->character->stat_total_dodge_rating;
        $this->weapondamage = $this->character->stat_weapon_damage;
        $this->damage_normal = $this->strength + $this->weapondamage;
        $this->damage_bonus = $this->damage_normal;
        $this->setMissile($this->getItemFromSlot('missiles_item_id'));
    }
    
    public function __endRequest(){
        //Change missiles
        $missile = $this->getItemFromSlot('missiles_item_id');
        if($missile == null || $missile->charges <= 0){
            if($missile != null)
                $missile->remove();
            $slotname = '';
            for($i=1; $i <= 4; $i++){
                $slotname = "missiles{$i}_item_id";
                $newMissile = $this->getItemFromSlot($slotname);
                if($newMissile != null){
                    if($newMissile->charges <= 0){
                        $newMissile->remove();
                        continue;
                    }
                    $this->setItemInInventory(null, $slotname);
                    $this->setItemInInventory($newMissile, 'missiles_item_id');
                    Core::req()->data['inventory']['missiles_item_id'] = $this->inventory->missiles_item_id;
                    Core::req()->data['inventory'][$slotname] = $this->inventory->{$slotname};
                    break;
                }
            }
            
        }
    }
    
    public function haveSlotmachineFreeSpin(){
        return $this->getUnusedResource(ResourceType::FreeSlotMachineSpin) >= Config::get('constants.resource_free_slotmachine_spin_usage_amount')
            || $this->getUnusedResource(ResourceType::SlotMachineJetons) >= Config::get('constants.resource_slotmachine_jeton_usage_amount');
    }
    
    public function isStorageUpgraded(){
        return $this->user->hasSetting('storage_upgraded');
    }
    
    public function maximumTrainingStorage(){
        if($this->isStorageUpgraded())
            return Config::get('constants.maximum_training_storage_amount_upgraded');
        return Config::get('constants.maximum_training_storage_amount');
    }
    
    public function maximumEnergyStorage(){
        if($this->isStorageUpgraded())
            return Config::get('constants.maximum_energy_storage_amount_upgraded');
        return Config::get('constants.maximum_energy_storage_amount');
    }
    
    public function getDailyBonuses(){
        $dateDiff = Utils::diffDate($this->character->ts_last_daily_login_bonus);
        if($dateDiff == -1){ //Yesterday -1 day
            $this->character->daily_login_bonus_day++;
            $this->character->ts_last_daily_login_bonus = time();
        }else if($dateDiff < -1){ //-x days
            $this->character->daily_login_bonus_day = 1;
            $this->character->ts_last_daily_login_bonus = time();
        }else
            return FALSE;
        //Get bonuses
        $rewards = Config::get("constants.daily_login_bonus_rewards");
        $rewards_pools = Config::get("constants.daily_login_bonus_rewards_pool");
        $pool_count = count($rewards_pools);
        $fixedDays = Config::get('constants.daily_login_bonus_reward_fixed_days');
        $currentDay = $this->character->daily_login_bonus_day;
        $dailyLogin = [];
        for($i = 1; $i <= $fixedDays; $i++){
            $day = $i;
            if($currentDay > 5){
                $day = ($currentDay - 2 + $i - 1);
                if($day > $currentDay) break;
                if($day < 6)
                    $bonus = $rewards[$day];
                else
                    $bonus = $rewards_pools[($day % $pool_count)];
            }else
                $bonus = $rewards[$day];
            $dailyLogin[$day] = [
                'rewardType1'=> $bonus['reward_type1'],
                'rewardType2'=> $bonus['reward_type2']
            ];
            if($currentDay == $day){
                //Calculate rewards and give to player
                Utils::calculateDailyBonus($this, $bonus, $amount1, $amount2);
                $dailyLogin[$day]['rewardType1Amount']= $amount1;
                $dailyLogin[$day]['rewardType2Amount']= $amount2;
				$this->character->league_fight_count = 0;
            }
        }
        return $dailyLogin;
    }
    
    public function getUnusedResource($type){
        $data = json_decode($this->character->unused_resources, TRUE);
        return isset_or($data[$type], 0);
    }
    
    public function giveUnusedResource($type, $amount){
        $data = json_decode($this->character->unused_resources, TRUE);
        $data[$type] = max(isset_or($data[$type], 0)+$amount, 0);
        $this->character->unused_resources = json_encode($data);
    }
    
    public function getMoney(){
        return $this->character->game_currency;
    }
    
    public function giveMoney($money){
        $this->character->game_currency += $money;
        if($money > 0)
            $this->incrementGoalStat('game_currency_collected', $money);
    }
    
    public function setMoney($money){
        $this->character->game_currency = $money;
    }
    
    public function getPremium(){
        return $this->user->premium_currency;
    }
    
    public function givePremium($prem){
        $this->user->premium_currency += $prem;
        if($prem > 0)
            $this->incrementGoalStat('premium_currency_collected', $prem);
        if($prem < 0)
            $this->incrementGoalStat('donuts_spent', abs($prem));
        if(Core::$PLAYER->user->id == $this->user->id)
            Core::req()->append['user']= $this->user;
    }
    
    public function setPremium($prem){
        $this->user->premium_currency = $prem;
    }
    
    public function getHonor(){
        return $this->character->honor;
    }
    
    public function getLeaguePoints(){
        return $this->character->league_points;
    }		
	
    public function giveHonor($h){
        $this->character->honor += $h;
        if($this->character->honor < 0)
            $this->character->honor = 0;
    }
    
    public function giveLeaguePoints($h){
        $this->character->league_points += $h;
        if($this->character->league_points < 0)
            $this->character->league_points = 0;
    }	
	
    public function setHonor($h){
        $this->character->honor = max($h, 0);
    }
    
    public function getExp(){
        return $this->character->xp;
    }
    
    public function giveExp($exp){
        $this->character->xp += $exp;
        if($this->character->xp < 0)
            $this->character->xp = 0;
        if($this->inventory->sidekick_id){
            $this->sidekicks->xp += round($exp * GameSettings::getConstant('sidekick_xp_factor'));
            if($this->sidekicks->xp < 0)
                $this->sidekicks->xp = 0;
            $this->calculateSidekickLVL();
        } 
        $this->calculateLVL();
    }
    
    public function setExp($exp){
        $this->character->xp = max($exp, 0);
        $this->calculateLVL();
    }
    
    public function getLVL(){
        return $this->character->level;
    }
    
    public function regenerateSometime(){
        //Store, refil quest energy
        $this->character->current_energy_storage = min($this->character->current_energy_storage + $this->character->quest_energy, $this->maximumEnergyStorage());
        $this->character->quest_energy = $this->character->max_quest_energy;
        $this->character->quest_energy_refill_amount_today = 0;
        //Store, refil training count
        $this->character->current_training_storage = min($this->character->current_training_storage + $this->character->training_count, $this->maximumTrainingStorage());
        $this->character->training_count = $this->character->max_training_count;
        //Give additional training points from guild booster
        if($this->character->guild_id != 0 && ($booster = $this->guild->getBoosters('quest')) != null)
            $this->character->training_count = Config::get("constants.guild_boosters.$booster.amount") + $this->character->max_training_count;
        //Slotmachine
        //TODO: check if event exists
        $this->giveUnusedResource(ResourceType::FreeSlotMachineSpin, Config::get('constants.resource_free_slotmachine_spin_usage_amount'));
        $this->character->slotmachine_spin_count = 0;
        // Reset herobook daily renewal counter
        $this->character->herobook_objectives_renewed_today = 0;
        // Reset daily goal counters
        $goalStats = $this->getGoalStats();
        $dailyKeys = ['quest_refreshed_a_day', 'shop_refreshed_a_day', 'coins_spent_a_day',
            'duels_started_a_day', 'league_fights_started_a_day', 'booster_sense_used_a_day'];
        foreach($dailyKeys as $key){
            if(isset($goalStats[$key])) $goalStats[$key] = 0;
        }
        $this->character->goal_stats = json_encode($goalStats);
    }
    
    public function refreshDuelStamina(){
        if($this->character->duel_stamina >= $this->character->max_duel_stamina)
            return;
        if($this->character->duel_stamina < $this->character->duel_stamina_cost)
            $totalSecs = round(1 / Config::get('constants.duel_stamina_refresh_amount_per_minute_first_duel') * 60);
        else
            $totalSecs = round(1 / Config::get('constants.duel_stamina_refresh_amount_per_minute') * 60);
        $amount = floor((time() - $this->character->ts_last_duel_stamina_change) / $totalSecs);
        if($amount > 0){
            $this->character->ts_last_duel_stamina_change = time();
            $this->character->duel_stamina = min($this->character->duel_stamina + $amount, $this->character->max_duel_stamina);
        }
    }

    public function refreshLeagueStamina(){
        if($this->character->league_stamina >= $this->character->max_league_stamina)
            return;
       
	  /* if($this->character->league_stamina < $this->character->duel_stamina_cost)
            $totalSecs = round(1 / Config::get('constants.league_stamina_refresh_amount_per_minute') * 60);
        else
            $totalSecs = round(1 / Config::get('constants.league_stamina_refresh_amount_per_minute') * 60);*/
		
		if($this->character->active_league_booster_id == 'booster_league1') {
			$totalSecs = round(1 / Config::get('constants.league_stamina_refresh_amount_per_minute_first_fight_booster1') * 60);	
		} elseif($this->character->active_league_booster_id == 'booster_league2') {
			$totalSecs = round(1 / Config::get('constants.league_stamina_refresh_amount_per_minute_first_fight_booster2') * 60);	
		} else {
			$totalSecs = round(1 / Config::get('constants.league_stamina_refresh_amount_per_minute_first_fight_nonbooster') * 60);	
		}
		
        $amount = floor((time() - $this->character->ts_last_league_stamina_change) / $totalSecs);
        if($amount > 0){
            $this->character->ts_last_league_stamina_change = time();
            $this->character->league_stamina = min($this->character->league_stamina + $amount, $this->character->max_league_stamina);
        }
    }
   
    public function calculateSidekickLVL(){
        $levels = GameSettings::getConstant('sidekick_levels');
        $newLVL = -1;
        $maxlevels=count($levels);
        for($lvl=1,$cnt=$maxlevels-1; $lvl<$cnt; $lvl++){
            if($this->sidekicks->xp < $levels[$lvl]['xp'])
                break;
            if($this->sidekicks->xp >= $levels[$lvl]['xp'] && $this->sidekicks->xp < $levels[$lvl+1]['xp'])
                $newLVL = $lvl;
        }
        if($newLVL == -1)
            $newLVL = $maxlevels;
        //
        if($this->sidekicks->level != $newLVL){
            $this->sidekicks->level = $newLVL;
            if($newLVL >= count($levels))
                $this->incrementGoalStat('sidekick_maxed');
        }
    }

    public function calculateLVL(){
        $levels = Config::get('constants.levels');
        $newLVL = -1;
        $maxlevels=count($levels);
        for($lvl=1,$cnt=$maxlevels-1; $lvl<$cnt; $lvl++){
            if($this->getExp() < $levels[$lvl]['xp'])
                break;
            if($this->getExp() >= $levels[$lvl]['xp'] && $this->getExp() < $levels[$lvl+1]['xp'])
                $newLVL = $lvl;
        }
        if($newLVL == -1)
            $newLVL = $maxlevels;
        //
        if($newLVL > $this->character->level)
            $this->character->stat_points_available += ($newLVL - $this->character->level) * Config::get('constants.level_up_stat_points');
		//
        if($this->character->level != $newLVL){
            $this->character->level = $newLVL;
            //
            $max_stages = $this->character->max_quest_stage;
    		$unlock_stage = $this->calculateStages();
    		if($unlock_stage > $max_stages){
    		    $this->givePremium(($unlock_stage - $max_stages) * Config::get('constants.stage_level_up_premium_amount'));
    			for($i=$max_stages + 1; $i <= $unlock_stage; $i++)
    			    $this->generateQuestsAtStage($i, 3);
    		}
    		$this->character->max_quest_stage = $unlock_stage;

            if($this->character->level == 60) {
                $skills = randomSidekickSkills();
                $q = new Sidekicks([
                    'character_id'=>$this->character->id,
                    'identifier'=>"sidekick_dog1",
                    'quality'=>3,
                    'stat_base_stamina'=>60,
                    'stat_base_strength'=>100,
                    'stat_base_critical_rating'=>40,
                    'stat_base_dodge_rating'=>23,
                    'stat_stamina'=>60,
                    'stat_strength'=>100,
                    'stat_critical_rating'=>40,
                    'stat_dodge_rating'=>23,
                    'stage1_skill_id'=>$skills[0],
                    'stage2_skill_id'=>$skills[1],
                    'stage3_skill_id'=>$skills[2]
                ]);
                $q->save();

                $sidekick_data = array();
                $sidekick_data[] = $q->id;
                $this->character->received_sidekick = 1;
                $this->inventory->sidekick_data = json_encode(array("orders" => $sidekick_data));
            }
        }
    }
    
    public function calculateStages(){
		$stages = Config::get('constants.stages');
		for($i=1, $c = count($stages)-1; $i <= $c; $i++){
			if($this->character->level >= $stages[$i]["min_level"] && $this->character->level < $stages[$i+1]["min_level"])
				return $i;
		}
		return count($stages);
	}
    
    public function giveRewards($rew){
        if(is_string($rew))
            $rew = json_decode($rew, true);
        if(isset($rew['coins']))
            $this->giveMoney($rew['coins']);
        if(isset($rew['xp']))
            $this->giveExp($rew['xp']);
        if(isset($rew['honor']))
            $this->giveHonor($rew['honor']);
        if(isset($rew['league_points']))
            $this->giveLeaguePoints($rew['league_points']);
        if(isset($rew['premium']))
            $this->givePremium($rew['premium']);
        if(isset($rew['statPoints']))
            $this->character->stat_points_available += $rew['statPoints'];
        if(isset($rew['stat_points']))
            $this->character->stat_points_available += $rew['stat_points'];
        //if($rew['item'])
        //    $this->giveItem($rew);
        if(isset($rew['slotmachine_jetons']))
            $this->giveUnusedResource(ResourceType::SlotMachineJetons, $rew['slotmachine_jetons']);
        if(isset($rew['quest_energy']))
            $this->character->quest_energy += $rew['quest_energy'];
        if(isset($rew['training_sessions']))
            $this->character->training_count += $rew['training_sessions'];
    }
    
    public function getBoosters($type=false){
		$b = ["quest"=>null, "stats"=>null, "work"=>null];
		if($this->character->ts_active_quest_boost_expires > time()){
			$b["quest"] = $this->character->active_quest_booster_id;
		}
		if($this->character->ts_active_stats_boost_expires > time()){
			$b["stats"] = $this->character->active_stats_booster_id;
		}
		if($this->character->ts_active_work_boost_expires > time()){
			$b["work"] = $this->character->active_work_booster_id;
		}
		if($this->character->ts_active_league_boost_expires > time()){
			$b["league"] = $this->character->active_league_booster_id;
		}
		return !$type?$b:$b[$type];
	}
	
	public function hasMultitasking(){
        return $this->character->ts_active_multitasking_boost_expires == -1 || $this->character->ts_active_multitasking_boost_expires > time();
    }
    
    public function getItems(){
        $templates = GameSettings::getConstant('item_templates');
        $arr = [];
        foreach($this->items as $q){
            if(!isset($templates[$q->identifier])){
                continue;
            }
            $arr[] = $q->toArray();
        }
        return $arr;
    }
    
    public function getQuests(){
        $arr = [];
        foreach($this->quests as $q)
            $arr[] = $q->toArray();
        return $arr;
    }
    
    public function getBattleQuest(){
        foreach($this->quests as $q){
            if($q->fight_battle_id != 0)
                return $q;
        }
        return null;
    }

    public function getBattleDungeons(){
        foreach($this->dungeon_quests as $q){
            if($q->battle_id != 0)
                return $q;
        }
        return null;
    }
    
    public function setItemInInventory($item, $slot){
        if(is_null($item)) $itemid = 0; else $itemid = $item->id;
        $this->inventory->{$slot} = $itemid;
    }
    
    public function createItem($data){
        $templates = GameSettings::getConstant('item_templates');
        if(!isset($data['identifier']) || !isset($templates[$data['identifier']])){
            return null;
        }
        $data['character_id'] = $this->character->id;
        $i = new Items($data);
        $i->save();
        $this->items[] = $i;
        return $i;
    }
    
    public function getItemFromSlot($slotname){
        return $this->getItemById($this->inventory->{$slotname});
    }
    
    public function getItemFromBankSlot($slotname){
        return $this->getItemById($this->bankinv->{$slotname});
    }
    
    public function removeItem($item){
        foreach($this->items as $key=>$it){
            if($it->id != $item->id)
                continue;
            $item->remove();
            unset($this->items[$key]);
            return true;
        }
        return false;
    }
    
    public function getOnlyEquipedItems(){
        $inventory=[];
        $items=[];
        for($i=1; $i<=8; $i++){
			$itemName = Item::$TYPE[$i];
			$item = $this->getItemFromSlot("{$itemName}_item_id");
			$inventory["{$itemName}_item_id"] = $item==null?0:$item->id;
			if($item != null)
				$items[] = $item;
		}
        $inventory["sidekick_id"] = $this->inventory->sidekick_id;
		$inventory["item_set_data"] = $this->getItemFromSlot("item_set_data")==null?0:$this->getItemFromSlot("item_set_data");
        return array('inventory'=>$inventory, 'items'=>$items);
    }
    
    public function findEmptyInventorySlot(){
        $lvl = $this->character->level;
        if($lvl >= Config::get('constants.inventory_bag3_unlock_level'))
            $slots = 18;
        else if($lvl >= Config::get('constants.inventory_bag2_unlock_level') && $lvl < Config::get('constants.inventory_bag3_unlock_level'))
            $slots = 12;
        else
            $slots = 6;
        for($i=1; $i <= $slots; $i++){
            $slotname = "bag_item{$i}_id";
            if($this->getItemFromSlot($slotname) == null)
                return $slotname;
        }
        return null;
    }
    
    public function getItemById($id){
        if($id <= 0) return null;
        foreach($this->items as $item){
            if($item->id == $id)
                return $item;
        }
        return null;
    }
    
    public function createQuest($data=[], $stage=1){
        $data['character_id'] = $this->character->id;
        $data['stage'] = $stage;
        $q = new Quests($data);
        $q->save();
        $this->quests[] = $q;
        return $q;
    }

    public function updateQuestsPool(){
        $qs = [];
        $aqid = 0;
        foreach($this->quests as $q){
            if($q->status < 5)
                $qs[$q->stage][] = $q->id;
            if($q->status > 1 && $q->status < 5)
                $aqid = $q->id;
        }
        $this->character->active_quest_id = $aqid;
        $this->character->quest_pool = json_encode($qs);
    }

    public function getDungeonQuestById($id){
        foreach($this->dungeon_quests as $q)
            if($q->id == $id)
                return $q;
        return null;
    }
    
    public function getDungeonByDungeonId($id){
        foreach($this->dungeons as $q)
            if($q->id == $id)
                return $q;
        return null;
    }

    public function getDungeonById($id){
        foreach($this->dungeons as $q)
            if($q->identifier == "dungeon{$id}")
                return $q;
        return null;
    }
    
    public function getQuestById($id){
        foreach($this->quests as $q)
            if($q->id == $id)
                return $q;
        return null;
    }
    
    public function getQuestsByStage($stage){
        $arr = [];
        foreach($this->quests as $q)
            if($q->stage == $stage)
                $arr[] = $q;
        return $arr;
    }
    
    public function generateQuestAtDungeon($dungeon, $dungeon_id, $stage, $mode){
        /*$stageQuests = $this->getDungeonByStage($stage);
        for($i=0, $c=count($stageQuests)-$count; $i<$c; $i++){
            $stageQuests[$i]->remove();
            unset($stageQuests[$i]);
        }*/
        $data = Utils::randomiseDungeonQuest($this, $dungeon, $dungeon_id, $stage, $mode);
        $data["character_id"] = $this->character->id;
        $q = new DungeonQuests($data);
        $q->save();

        $this->dungeon_quests[] = $q;
        return $q->id;
    }

    public function generateQuestsAtStage($stage, $count, &$isAnyItem=false){
        $qCount = 0;
        $stageQuests = $this->getQuestsByStage($stage);
        for($i=0, $c=count($stageQuests)-$count; $i<$c; $i++){
            $stageQuests[$i]->remove();
            unset($stageQuests[$i]);
        }
        foreach($stageQuests as $q){
            $q->reset(['id','character_id']);
            $q->setData(Utils::randomiseQuest($this, $stage, true, $isAnyItem));
            $qCount++;
        }
        for($i=0; $i < $count - $qCount; $i++)
            $this->createQuest(Utils::randomiseQuest($this, $stage, false, $isAnyItem), $stage);
        $this->updateQuestsPool();
    }
    
    public function setTutorialFlag($flag, $val=true){
        $flags = json_decode($this->character->tutorial_flags, true);
        $flags[$flag] = $val;
        $this->character->tutorial_flags = json_encode($flags);
    }
    
    public function getTutorialFlag($flag){
        $tut = json_decode($this->character->tutorial_flags, true);
        if(isset($tut[$flag]) && $tut[$flag] == true)
            return true;
        return false;
    }

    // Goal stat tracking (stored as JSON in character.goal_stats)
    public function getGoalStats(){
        $raw = $this->character->goal_stats;
        if(!$raw) return [];
        $stats = json_decode($raw, true);
        return is_array($stats) ? $stats : [];
    }

    public $goalStatsChanged = false;

    public function incrementGoalStat($key, $amount = 1){
        $stats = $this->getGoalStats();
        $stats[$key] = ($stats[$key] ?? 0) + $amount;
        $this->character->goal_stats = json_encode($stats);
        $this->goalStatsChanged = true;
        return $stats[$key];
    }

    public function setGoalStat($key, $value){
        $stats = $this->getGoalStats();
        $stats[$key] = $value;
        $this->character->goal_stats = json_encode($stats);
        $this->goalStatsChanged = true;
    }

    // Build lookup_column => value map from tracked stats + derived character data
    public function getGoalStatsMap(){
        $stats = $this->getGoalStats();

        $stats['level_reached'] = $this->character->level;
        $stats['stage_reached'] = $this->character->max_quest_stage;
        $stats['honor_reached'] = $this->character->honor;
        $stats['league_points_reached'] = $this->character->league_points;
        $stats['guild_joined'] = $this->character->guild_id > 0 ? 1 : 0;
        $stats['all_stats_value_reached'] = min(
            $this->character->stat_base_stamina,
            $this->character->stat_base_strength,
            $this->character->stat_base_critical_rating,
            $this->character->stat_base_dodge_rating
        );
        $stats['herobook_objectives_finished'] = $this->character->herobook_objectives_finished;

        if($this->getTutorialFlag('first_mission'))
            $stats['tutorial_completed'] = 1;

        $stats['guild_donated'] = $this->character->guild_donated_game_currency
            + $this->character->guild_donated_premium_currency;

        $stats['account_confirmed'] = $this->user->confirmed ? 1 : 0;

        // Quest completion flags from quests table
        if(!empty($this->quests)){
            foreach($this->quests as $q){
                if($q->status == 5)
                    $stats[$q->identifier] = ($stats[$q->identifier] ?? 0) + 1;
            }
        }

        // Check if all 8 equipment slots are filled
        if($this->inventory){
            $equipped = 0;
            for($i = 1; $i <= 8; $i++){
                $slot = \Cls\Utils\Item::$TYPE[$i] . '_item_id';
                if($this->getItemFromSlot($slot) != null)
                    $equipped++;
            }
            if($equipped >= 8)
                $stats['character_full_equipped'] = 1;
        }

        // Dungeon unlock/completion flags
        if(!empty($this->dungeons)){
            foreach($this->dungeons as $d){
                if($d->status >= 3)
                    $stats[$d->identifier.'_unlocked'] = 1;
                if($d->status >= 4)
                    $stats[$d->identifier.'_normal_completed'] = 1;
                if($d->status >= 4 && $d->mode >= 1)
                    $stats[$d->identifier.'_hard_completed'] = 1;
            }
        }

        return $stats;
    }

    // Build current_goal_values for SWF: keyed by goal identifier, each {"value":X,"current_value":Y}
    public function getGoalValues(){
        $statsMap = $this->getGoalStatsMap();
        $goals = \Cls\GameSettings::getConstant('goals');
        $result = [];

        foreach($goals as $id => $goal){
            if(!$goal['active']) continue;
            $col = $goal['lookup_column'];
            $val = (int)($statsMap[$col] ?? 0);
            $result[$id] = ['value' => $val, 'current_value' => $val];
        }

        return (object)$result;
    }

    // Load collected goals for SWF: array of {goal_name: {"value":milestone,"date":"datetime"}}
    public function getCollectedGoals(){
        $rows = DB::sql(
            "SELECT goal_name, milestone_value, collected_at FROM collected_goals WHERE character_id = " . intval($this->character->id)
        )->fetchAll(\PDO::FETCH_ASSOC);

        $collected = [];
        foreach($rows as $row){
            $collected[] = [
                $row['goal_name'] => [
                    'value' => (int)$row['milestone_value'],
                    'date' => date('Y-m-d H:i:s', (int)$row['collected_at'])
                ]
            ];
        }
        return $collected;
    }

    public function calculateStats(){
        $boosterVal = 1;
        if(($booster = $this->getBoosters('stats')) != null)
            $boosterVal += (Config::get("constants.boosters.$booster.amount")/100);
        if($this->character->guild_id != 0)
            $boosterVal += ($this->guild->stat_character_base_stats_boost/100);
        $this->character->stat_total_stamina = ceil($this->character->stat_base_stamina * $boosterVal);
        $this->character->stat_total_strength = ceil($this->character->stat_base_strength * $boosterVal);
        $this->character->stat_total_critical_rating = ceil($this->character->stat_base_critical_rating * $boosterVal);
        $this->character->stat_total_dodge_rating = ceil($this->character->stat_base_dodge_rating * $boosterVal);
        for($i=1; $i <= 8; $i++){
            $slot = \Cls\Utils\Item::$TYPE[$i].'_item_id';
            $item = $this->getItemFromSlot($slot);
            if($item == null) continue;
            $this->character->stat_total_stamina += $item->stat_stamina;
            $this->character->stat_total_strength += $item->stat_strength;
            $this->character->stat_total_critical_rating += $item->stat_critical_rating;
            $this->character->stat_total_dodge_rating += $item->stat_dodge_rating;
            $this->character->stat_weapon_damage += $item->stat_weapon_damage;
        }
        //var_dump($this->inventory->sidekick_id);
        if($this->inventory->sidekick_id > 0 && $this->sidekicks){
            //var_dump($this->sidekicks);
            if($this->sidekicks->level >= 20){
                if($this->sidekicks->stage2_skill_id == 6){
                    $this->character->stat_total_strength += ceil($this->character->stat_base_strength * 1.10);
                }

                if($this->sidekicks->stage2_skill_id == 7){
                    $this->character->stat_total_stamina += ceil($this->character->stat_base_stamina * 1.10);
                }
            }

            $this->character->stat_total_stamina += $this->sidekicks->stat_stamina;
            $this->character->stat_total_strength += $this->sidekicks->stat_strength;
            $this->character->stat_total_critical_rating += $this->sidekicks->stat_critical_rating;
            $this->character->stat_total_dodge_rating += $this->sidekicks->stat_dodge_rating;
        }
        $this->character->stat_total = $this->character->stat_total_stamina + $this->character->stat_total_strength + $this->character->stat_total_critical_rating + $this->character->stat_total_dodge_rating;
    }
    
    public static function login($email, $password){
        $user = User::find(function($q) use($email,$password){
            $q->where('email',$email)->where('password_hash',Core::passwordHash($password));
        });
        if(!$user)
            return FALSE;
        $player = new Player();
        $player->user = $user;
        $player->loadPlayer();
        return $player;
    }
    
    public static function findBySSID($uid, $ssid){
        $user = User::find(function($q) use($uid,$ssid){
            $q->where('id',$uid)->where('session_id',$ssid);
        });
        if(!$user)
            return NULL;
        $player = new Player();
        $player->user = $user;
        $player->loadPlayer();
        return $player;
    }
    
    public static function findByUserId($uid){
        $user = User::find(function($q) use($uid){
            $q->where('id',$uid);
        });
        if(!$user)
            return NULL;
        $player = new Player();
        $player->user = $user;
        return $player;
    }
    
    public static function findByCharacterId($chid){
        $character = Character::find(function($q) use($chid){
            $q->where('id',$chid);
        });
        if(!$character)
            return NULL;
        $user = User::find(function($q) use($character){
            $q->where('id',$character->user_id);
        });
        $player = new Player();
        $player->user = $user;
        $player->character = $character;
        return $player;
    }
    
    public static function findAllByGuildId($gid, $bypass = false){
        $characters = Character::findAll(function($q) use($gid,$bypass){
            $q->where('guild_id', $gid);
            if($bypass)
                $q->where('id','<>',$bypass);
        });
        if(!$characters || !count($characters))
            return [];
        $players = [];
        foreach($characters as $char){
            $player = new Player();
            $player->character = $char;
            $players[] = $player;
        }
        return $players;
    }

    // ---- Herobook ----

    public function getHerobookObjectives(){
        $rows = DB::sql(
            "SELECT * FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND status IN (1,2,3) ORDER BY duration_type ASC, objective_index ASC"
        )->fetchAll(\PDO::FETCH_ASSOC);

        $result = new \stdClass();
        $result->newObjectivesCreated = false;
        foreach($rows as $row){
            $obj = new \stdClass();
            $obj->id = (int)$row['id'];
            $obj->identifier = $row['identifier'];
            $obj->type = (int)$row['type'];
            $obj->duration_type = (int)$row['duration_type'];
            $obj->status = (int)$row['status'];
            $obj->current_value = (int)$row['current_value'];
            $obj->max_value = (int)$row['max_value'];
            $obj->ts_end = (int)$row['ts_end'];
            $obj->index = (int)$row['objective_index'];
            $obj->rewards = $row['rewards'];
            $result->{$row['id']} = $obj;
        }
        return $result;
    }

    public function generateHerobookObjectives(){
        if($this->getLVL() < GameSettings::getConstant('herobook_min_level'))
            return;

        $now = time();

        // Expire old objectives
        DB::sql(
            "UPDATE herobook_objectives SET status = 4 WHERE character_id = " . intval($this->character->id) .
            " AND status = 1 AND ts_end < " . $now
        );

        // Count active daily/weekly
        $dailyCount = (int)DB::sql(
            "SELECT COUNT(*) FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND duration_type = 1 AND status IN (1,2) AND ts_end >= " . $now
        )->fetchColumn();

        $weeklyCount = (int)DB::sql(
            "SELECT COUNT(*) FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND duration_type = 2 AND status IN (1,2) AND ts_end >= " . $now
        )->fetchColumn();

        $maxDaily = (int)GameSettings::getConstant('herobook_daily_objectives');
        $maxWeekly = (int)GameSettings::getConstant('herobook_weekly_objectives');

        $created = false;
        if($dailyCount < $maxDaily){
            $this->createHerobookObjectives(1, $maxDaily - $dailyCount);
            $created = true;
        }
        if($weeklyCount < $maxWeekly){
            $this->createHerobookObjectives(2, $maxWeekly - $weeklyCount);
            $created = true;
        }
        return $created;
    }

    private function createHerobookObjectives($durationType, $count){
        $objectives = GameSettings::getConstant('herobook_objectives');
        $level = $this->getLVL();

        // Filter by duration type and level
        $eligible = [];
        foreach($objectives as $id => $obj){
            if($obj['min_level'] > $level) continue;
            if($durationType == 1 && $obj['daily'] <= 0) continue;
            if($durationType == 2 && $obj['weekly'] <= 0) continue;
            $eligible[$id] = $obj;
        }

        if(empty($eligible)) return;

        // Get existing active identifiers to avoid duplicates
        $existing = DB::sql(
            "SELECT identifier FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND status IN (1,2) AND ts_end >= " . time()
        )->fetchAll(\PDO::FETCH_COLUMN);
        $existingMap = array_flip($existing);

        // Get next index
        $maxIndex = (int)DB::sql(
            "SELECT COALESCE(MAX(objective_index), 0) FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND status IN (1,2,3)"
        )->fetchColumn();

        $keys = array_keys($eligible);
        shuffle($keys);

        $tsEnd = $this->getHerobookExpiry($durationType);
        $created = 0;

        foreach($keys as $id){
            if($created >= $count) break;
            if(isset($existingMap[$id])) continue;

            $obj = $eligible[$id];
            $baseValue = $durationType == 1 ? $obj['daily'] : $obj['weekly'];
            $variance = $obj['variance'];
            $maxValue = $this->calculateHerobookMaxValue($baseValue, $variance, $obj['scaling'], $level);
            $rewards = $this->rollHerobookReward($durationType, $level);

            $maxIndex++;
            DB::sql(
                "INSERT INTO herobook_objectives (character_id, identifier, type, duration_type, status, current_value, max_value, ts_end, objective_index, rewards) VALUES (" .
                intval($this->character->id) . ", '" . addslashes($id) . "', " . intval($obj['type']) . ", " .
                $durationType . ", 1, 0, " . $maxValue . ", " . $tsEnd . ", " . $maxIndex . ", '" . addslashes($rewards) . "')"
            );
            $created++;
        }
    }

    public function getHerobookExpiry($durationType){
        $now = time();
        if($durationType == 1){
            // Daily: next midnight
            return strtotime('tomorrow midnight');
        }
        // Weekly: next Monday midnight
        $dow = date('N'); // 1=Monday, 7=Sunday
        $daysUntilMonday = (8 - $dow) % 7;
        if($daysUntilMonday == 0) $daysUntilMonday = 7;
        return strtotime("+{$daysUntilMonday} days midnight");
    }

    public function calculateHerobookMaxValue($base, $variance, $scaling, $level){
        if($base <= 0) return 1;
        $val = $base;
        if($scaling && $level > 1){
            $val = (int)ceil($base * (1 + ($level - 1) * 0.02));
        }
        if($variance > 0){
            $min = (int)ceil($val * (1 - $variance / 100));
            $max = (int)ceil($val * (1 + $variance / 100));
            $val = mt_rand(max(1, $min), max(1, $max));
        }
        return max(1, $val);
    }

    public function rollHerobookReward($durationType, $level){
        $tiers = GameSettings::getConstant($durationType == 1 ? 'herobook_daily_rewards' : 'herobook_weekly_rewards');
        $totalChance = 0;
        foreach($tiers as $tier) $totalChance += $tier['chance'];
        $roll = mt_rand(1, $totalChance);

        $cumulative = 0;
        $selected = $tiers[0];
        foreach($tiers as $tier){
            $cumulative += $tier['chance'];
            if($roll <= $cumulative){
                $selected = $tier;
                break;
            }
        }

        $reward = [];
        $type = $selected['type'];
        $amount = $selected['amount'];
        $factor = $selected['reward_factor'];
        $xpTime = (int)GameSettings::getConstant('herobook_objective_reward_xp_time');
        $coinTime = (int)GameSettings::getConstant('herobook_objective_reward_game_currency_time');

        switch($type){
            case 1: // XP
                $reward['xp'] = (int)max(1, floor($xpTime * pow($level, 1.3) * $factor * 0.01));
                break;
            case 2: // Game currency
                $reward['coins'] = (int)max(1, floor($coinTime * pow($level, 1.6) * $factor * 0.01));
                break;
            case 3: // Stat points
                $reward['stat_points'] = $amount;
                $reward['statPoints'] = $amount;
                break;
            case 4: // Training sessions
                $reward['training_sessions'] = $amount;
                break;
            case 5: // Item
                $reward['coins'] = (int)max(1, floor($coinTime * pow($level, 1.6) * 0.02));
                break;
            case 6: // Quest energy
                $reward['quest_energy'] = $amount;
                break;
            case 7: // Premium currency
                $reward['premium'] = $amount;
                break;
        }
        return json_encode($reward);
    }

    public function updateHerobookProgress($type, $amount = 1){
        if($this->getLVL() < GameSettings::getConstant('herobook_min_level'))
            return;

        $rows = DB::sql(
            "SELECT id, current_value, max_value FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND type = " . intval($type) . " AND status = 1 AND ts_end >= " . time()
        )->fetchAll(\PDO::FETCH_ASSOC);

        foreach($rows as $row){
            $newVal = min((int)$row['current_value'] + $amount, (int)$row['max_value']);
            $newStatus = $newVal >= (int)$row['max_value'] ? 2 : 1;
            DB::sql(
                "UPDATE herobook_objectives SET current_value = " . $newVal . ", status = " . $newStatus .
                " WHERE id = " . (int)$row['id']
            );
        }
    }

    // ---- Event Quests ----

    private $_eventQuest = false;

    public function getActiveEventQuestRecord(){
        if($this->character->event_quest_id <= 0) return null;
        if($this->_eventQuest !== false) return $this->_eventQuest;
        $this->_eventQuest = \Schema\EventQuests::find(function($q){
            $q->where('id', $this->character->event_quest_id);
        });
        return $this->_eventQuest;
    }

    public function getEventQuest(){
        $eq = $this->getActiveEventQuestRecord();
        if(!$eq) return null;

        $now = time();
        $endTs = strtotime($eq->end_date);
        if($endTs && $endTs < $now && $eq->status == 1){
            $allDone = $this->checkAllObjectivesCollected($eq);
            $eq->status = $allDone ? 5 : 3;
        }

        $data = [
            'id' => $eq->id,
            'identifier' => $eq->identifier,
            'status' => $eq->status,
            'end_date' => $eq->end_date,
            'objective1_value' => $eq->objective1_value,
            'objective2_value' => $eq->objective2_value,
            'objective3_value' => $eq->objective3_value,
            'objective4_value' => $eq->objective4_value,
            'objective5_value' => $eq->objective5_value,
            'objective6_value' => $eq->objective6_value,
            'rewards' => $eq->rewards,
            'reward_item1_id' => $eq->reward_item1_id,
            'reward_item2_id' => $eq->reward_item2_id,
            'reward_item3_id' => $eq->reward_item3_id,
        ];
        return $data;
    }

    private function checkAllObjectivesCollected($eq){
        $events = GameSettings::getConstant('event_quests');
        $event = $events[$eq->identifier] ?? null;
        if(!$event) return false;
        foreach($event['objectives'] as $obj){
            $idx = $obj['index'];
            $field = 'objective' . $idx . '_value';
            $val = $eq->{$field};
            if($val <= $obj['value']) return false;
        }
        return true;
    }

    public function getActiveEventForLogin(){
        $events = GameSettings::getConstant('event_quests');
        $minLevel = GameSettings::getConstant('event_quest_min_level');
        if($this->getLVL() < $minLevel) return null;
        $now = time();
        foreach($events as $id => $event){
            $start = strtotime($event['start_date']);
            $end = strtotime($event['end_date']);
            if($start && $end && $now >= $start && $now <= $end){
                return ['identifier' => $id, 'end_date' => $event['end_date']];
            }
        }
        return null;
    }

    public function updateEventQuestProgress($type, $reference = '', $amount = 1){
        if($this->character->event_quest_id <= 0) return;
        $eq = $this->getActiveEventQuestRecord();
        if(!$eq || $eq->status != 1) return;

        $endTs = strtotime($eq->end_date);
        if($endTs && $endTs < time()) return;

        $events = GameSettings::getConstant('event_quests');
        $event = $events[$eq->identifier] ?? null;
        if(!$event) return;

        $changed = false;
        foreach($event['objectives'] as $objId => $obj){
            if($obj['type'] != $type) continue;

            if($type == 7){
                if($obj['reference'] != $reference) continue;
            } elseif($type == 8){
                if($obj['reference'] != '' && $obj['reference'] != '*'){
                    if(!$this->matchesWildcard($obj['reference'], $reference))
                        continue;
                }
            } elseif($type == 6){
                if($obj['reference'] != '' && $obj['reference'] != $reference)
                    continue;
            }

            $idx = $obj['index'];
            $field = 'objective' . $idx . '_value';
            $current = $eq->{$field};
            $target = $obj['value'];

            if($current >= $target) continue;

            $eq->{$field} = min($current + $amount, $target);
            $changed = true;
        }

        if($changed) $eq->save();
    }

    public function rollEventItem(){
        if($this->character->event_quest_id <= 0) return;
        $eq = $this->getActiveEventQuestRecord();
        if(!$eq || $eq->status != 1) return;

        $endTs = strtotime($eq->end_date);
        if($endTs && $endTs < time()) return;

        $events = GameSettings::getConstant('event_quests');
        $event = $events[$eq->identifier] ?? null;
        if(!$event) return;

        $eventItems = GameSettings::getConstant('event_items');

        foreach($event['objectives'] as $objId => $obj){
            if($obj['type'] != 7) continue;

            $itemRef = $obj['reference'];
            if(!isset($eventItems[$itemRef])) continue;

            $idx = $obj['index'];
            $field = 'objective' . $idx . '_value';
            $current = $eq->{$field};
            $target = $obj['value'];

            if($current >= $target) continue;

            $chance = $eventItems[$itemRef]['reward_chance'] ?? 0;
            if($chance <= 0) continue;

            if(mt_rand(1, 10000) <= (int)($chance * 10000)){
                $eq->{$field} = min($current + 1, $target);
                $eq->save();
            }
        }
    }

    private function matchesWildcard($pattern, $str){
        $regex = str_replace('\\*', '.*', '/^' . preg_quote($pattern, '/') . '$/');
        return (bool)preg_match($regex, $str);
    }

    public function rollHerobookItemDrop(){
        if($this->getLVL() < GameSettings::getConstant('herobook_min_level'))
            return null;

        // Get active herobook objectives for item collection (types 17, 18, 19)
        $rows = DB::sql(
            "SELECT id, type, identifier FROM herobook_objectives WHERE character_id = " . intval($this->character->id) .
            " AND type IN (17,18,19) AND status = 1 AND ts_end >= " . time()
        )->fetchAll(\PDO::FETCH_ASSOC);

        if(empty($rows)) return null;

        $objectives = GameSettings::getConstant('herobook_objectives');
        $herobookItems = GameSettings::getConstant('herobook_items');

        foreach($rows as $row){
            $objDef = $objectives[$row['identifier']] ?? null;
            if(!$objDef) continue;

            $itemRef = $objDef['reference'] ?? '';
            if(!$itemRef || !isset($herobookItems[$itemRef])) continue;

            $chance = $herobookItems[$itemRef]['reward_chance'] ?? 0;
            if($chance <= 0) continue;

            // Roll for drop
            if(mt_rand(1, 10000) <= (int)($chance * 10000)){
                $this->updateHerobookProgress((int)$row['type'], 1);

                // Determine quality key
                $qualityKey = 'herobook_item_common';
                if((int)$row['type'] == 18) $qualityKey = 'herobook_item_rare';
                if((int)$row['type'] == 19) $qualityKey = 'herobook_item_epic';

                return [$qualityKey => $itemRef];
            }
        }
        return null;
    }
}