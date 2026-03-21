<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Cls\Player;
use Schema\User;
use Schema\Dungeons;
use Schema\Sidekicks;
use Schema\WorldbossEvent;
use Schema\WorldbossAttack;
use Schema\WorldbossReward;
use Cls\GameSettings;

class loginUser{
    
    public function __request($player=null, $uid=false, $exssid = false){
        if(!$exssid || !$uid){
        	$email = getField('email', FIELD_EMAIL);
        	if(!User::exists(function($q)use($email){ $q->where('email',$email); }))
        		return Core::setError('errLoginNoSuchUser');
        	$pass = getField('password');
        	if(!$email || !$pass || !($player = Player::login($email, $pass)))
        		return Core::setError('errLoginInvalid');
        }else
        	if(!($player = Player::findBySSID($uid, $exssid)))
        		return Core::setError('errLoginNoSuchSessionId');
        		
        $player->user->session_id = md5(microtime());
        setcookie("ssid", $player->user->session_id, time() + 63072000, '/');
        $player->user->last_login_ip = getclientip();
        $player->user->ts_last_login = time();
        $player->user->login_count++;
        $locale = $_COOKIE['web-lang'] ?? null;
        if($locale && in_array($locale, ['pl_PL','en_GB','pt_BR']))
            $player->user->locale = $locale;
        
        $player->incrementGoalStat('days_logged_in');
        $dailyLogin = $player->getDailyBonuses();

        $herobookCreated = $player->generateHerobookObjectives();
        
        if(empty($player->dungeons)){
            for($i = 1; $i <= 9; $i++){
                $dungeons = new Dungeons([
                    'character_id'=>$player->character->id,
                    'identifier'=>'dungeon'.$i,
                    'status'=>2,
                ]);
                $dungeons->save();
                $player->dungeons[] = $dungeons;
            }
        }

        if(empty($player->sidekicks) && $player->getLVL() >= 60 && !$player->character->received_sidekick){
            $skills = randomSidekickSkills();
            $q = new Sidekicks([
                'character_id'=>$player->character->id,
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
            $player->character->received_sidekick = 1;
            $player->inventory->sidekick_data = json_encode(array("orders" => $sidekick_data));
            $player->incrementGoalStat('sidekick_collected');
            $player->incrementGoalStat('different_sidekick_collected');
        }

        Core::req()->data = array(
            "user"=>$player->user,
            "character"=>$player->character,
            "bank_inventory"=>$player->bankinv,
            "inventory"=>$player->inventory, //eq
            "items"=>$player->items, //itemy
            "quests"=>$player->quests, //questy
            "dungeons"=>$player->dungeons, //dungeons
            "dungeon_quests"=>$player->dungeon_quests, //dungeon quests
            "advertisment_info"=>$this->advInfo(),
            "bonus_info"=>$this->bonusInfo(),
            "campaigns"=>array(),
            "collected_goals"=>$player->getCollectedGoals(),
            "collected_item_pattern"=>json_decode($player->character->collected_item_pattern ?? '[]', true) ?: [],
            "current_goal_values"=>$player->getGoalValues(),
            "current_item_pattern_values"=>$this->itemPatt($player),
            "item_offers"=>array(),
            "league_locked"=>false,
            "league_season_end_timestamp"=>0,
            "local_notification_settings"=>$this->notif(),
            "login_count"=>$player->user->login_count,
            "missed_duels"=>0,
            "missed_league_fights"=>0,
            "new_guild_log_entries"=>0,
            "new_version"=>false,
            "reskill_enabled"=>false,
            "server_timestamp_offset"=>Core::getTimestampOffset(),
            "show_advertisment"=>false,
            "show_preroll_advertisment"=>false,
            "special_offers"=>array(),
            "tos_update_needed"=>false,
            "tournament_end_timestamp"=>$this->getTournamentEndTimestamp(),
            "user_geo_location"=>"xX",
            "worldboss_events"=>$this->getWorldbossData($player),
            "worldboss_event_character_data"=>$this->getWorldbossCharacterData($player)
        );
        if(isset($this->_wbPendingAttack))
            Core::req()->data['worldboss_attack'] = $this->_wbPendingAttack;
        if($player->guild != null){
        	Core::req()->data['guild']= $player->guild;
        	Core::req()->data['guild_members']=$player->guild->getMembers();
        	if(count($player->guild->getBattleRewards()))
        		Core::req()->data['guild_battle_rewards'] = $player->guild->getBattleRewards();
        	if(($finishedAttack = $player->guild->getFinishedAttack()) != NULL){
        		Core::req()->data['finished_guild_battle_attack'] = $finishedAttack->battle->getDataForAttacker();
        		Core::req()->data['guild_battle_guilds'][] = $finishedAttack->gDefender;
        	}
        	if(($finishedDefense = $player->guild->getFinishedDefense()) != NULL){
        		Core::req()->data['finished_guild_battle_defense'] = $finishedDefense->battle->getDataForDefender();
        		Core::req()->data['guild_battle_guilds'][] = $finishedDefense->gAttacker;
        	}
        	if(($pendingAttack = $player->guild->getPendingAttack()) != NULL){
        		Core::req()->data['pending_guild_battle_attack'] = $pendingAttack->battle->getDataForAttacker();
        		Core::req()->data['guild_battle_guilds'][] = $pendingAttack->gDefender;
        	}
        	if(($pendingDefense = $player->guild->getPendingDefense()) != NULL){
        		Core::req()->data['pending_guild_battle_defense'] = $pendingDefense->battle->getDataForDefender();
        		Core::req()->data['guild_battle_guilds'][] = $pendingDefense->gAttacker;
        	}
        }
        if($player->character->active_work_id)
        	Core::req()->data["work"]= $player->work;
        if($player->character->active_training_id)
        	Core::req()->data["training"]= $player->training;
        if($player->inventory->sidekick_id)
            Core::req()->data["sidekick"]= $player->sidekicks;
        //Core::req()->data += array('missed_duels'=>Core::db()->query('SELECT COUNT(*) FROM '.DataBase::getTable('duel').' WHERE `character_b_status` = 1 AND `character_b_id`='.$this->player->characterID)->fetch(PDO::FETCH_NUM)[0]);
		Core::req()->data["missed_duels"] = $player->getMissedDuels();
		Core::req()->data["missed_league_fights"] = $player->getMissedLeagueFights();
        if($player->battle)
        	Core::req()->data['battle'] = $player->battle;
        if($player->character->active_duel_id)
        	Core::req()->data['duel'] = $player->duel;
        if($player->character->active_league_fight_id)
        	Core::req()->data['league_fight'] = $player->league_fight;
        if(count($player->battles))
        	Core::req()->data['battles'] = $player->battles;
        Core::req()->data['new_messages'] = $player->getUnreadedMessagesCount();
        if($dailyLogin !== FALSE){
        	Core::req()->data['daily_login_bonus_rewards'] = $dailyLogin;
        	Core::req()->data['daily_login_bonus_day'] = $player->character->daily_login_bonus_day;
        }
        $herobookData = $player->getHerobookObjectives();
        if($herobookCreated)
            $herobookData->newObjectivesCreated = true;
        Core::req()->data['herobook_objectives'] = $herobookData;

        $eqData = $player->getEventQuest();
        if($eqData){
            Core::req()->data['event_quest'] = $eqData;
        } else {
            $activeEvent = $player->getActiveEventForLogin();
            if($activeEvent)
                Core::req()->data['event_quest'] = $activeEvent;
        }
    }
    
    private function advInfo(){
        $adv = [
			"show_advertisment"=> true,
			"show_preroll_advertisment"=> false,
			"show_left_skyscraper_advertisment"=> false,
			"show_pop_under_advertisment"=> false,
			"show_footer_billboard_advertisment"=> false,
			"advertisment_refresh_rate"=> 15,
			"mobile_interstitial_cooldown"=> 1800,
			"remaining_video_advertisment_cooldown__1"=> 0,
			"video_advertisment_blocked_time__1"=> 1800,
			"remaining_video_advertisment_cooldown__2"=> 0,
			"video_advertisment_blocked_time__2"=> 1800,
			"remaining_video_advertisment_cooldown__3"=> 0,
			"video_advertisment_blocked_time__3"=> 1800,
			"remaining_video_advertisment_cooldown__4"=> 0,
			"video_advertisment_blocked_time__4"=> 1800,
			"remaining_video_advertisment_cooldown__5"=> 0,
			"video_advertisment_blocked_time__5"=> 7200
		];
		return $adv;
    }
    
    private function bonusInfo(){
        $b = array(
				"quest_energy"=> 0,//$this->characterData["quest_energy"],
				"duel_stamina"=> 0,//$this->characterData["duel_stamina"],
				"league_stamina"=> 0,//$this->characterData["league_stamina"],
				"training_count"=> 0,//$this->characterData["training_count"]
			);
		return $b;
    }
    
    private function itemPatt($player){
        $defaults = [
			"biker", "costume", "disco", "doctor", "movie", "robinhood",
			"superherozero", "superheroset1", "superheroset2", "superheroset3",
			"olympia_2016_rio", "asian", "frogman1", "ironman1", "movienew",
			"musketeer", "overall", "powerset1", "powerset2", "safari",
			"nano", "pirates", "wrestling", "octoberfest", "halloween",
			"superhero", "work", "league_custom1", "league_custom2", "xmas"
		];
		$newPatterns = ["league_custom1", "league_custom2", "xmas"];

		$saved = json_decode($player->character->current_item_pattern_values ?? '{}', true) ?? [];
		$patt = [];
		foreach($defaults as $name){
			if(isset($saved[$name])){
				$patt[$name] = [
					"value" => $saved[$name]['value'] ?? 0,
					"collected_items" => $saved[$name]['collected_items'] ?? null,
					"is_new" => in_array($name, $newPatterns)
				];
			} else {
				$patt[$name] = ["value" => 0, "collected_items" => null, "is_new" => in_array($name, $newPatterns)];
			}
		}
		return $patt;
    }
    
    private function notif(){
        $t = array(
			"mission_finished"=> array(
				"id"=> 1,
				"active"=> true,
				"vibrate"=> false,
				"title"=> "HeroZ",
				"body"=> "Twoja misja została zakończona."
			),
			"training_finished"=> array(
				"id"=> 2,
				"active"=> true,
				"vibrate"=> false,
				"title"=> "HeroZ",
				"body"=> "Twój trening został zakończony."
			),
			"work_finished"=> array(
				"id"=> 3,
				"active"=> true,
				"vibrate"=> false,
				"title"=> "HeroZ",
				"body"=> "Twoja praca jest zakończona."
			),
			"free_duel_available"=> array(
				"id"=> 4,
				"active"=> true,
				"vibrate"=> false,
				"title"=> "HeroZ",
				"body"=> "Znowu masz wystarczająco dużo odwagi na swobodny atak."
			)
		);
		return $t;
    }

    private function getTournamentEndTimestamp(){
        $t = \Schema\Tournament::find(function($q){ $q->where('status', 1); });
        return $t ? $t->ts_end : 0;
    }

    private function getWorldbossData($player){
        $event = WorldbossEvent::find(function($q){
            $q->where('status', 1)->orWhere('status', 2)->orWhere('status', 4)->orderBy('id', 'desc')->limit(1);
        });
        if(!$event) return [];

        if($event->status == 1 && $player->character->worldboss_event_id != $event->id){
            $player->character->worldboss_event_id = $event->id;
        }

        $attacks = WorldbossAttack::findAll(function($q) use($event, $player){
            $q->where('worldboss_event_id', $event->id)->where('character_id', $player->character->id);
        });
        $ownAttackCount = 0;
        foreach($attacks as $atk){
            if($atk->status == 3) $ownAttackCount++;
        }

        $ranking = 0;
        if($ownAttackCount > 0){
            $rows = DB::sql("SELECT character_id, SUM(total_damage) as dmg FROM worldboss_attack WHERE worldboss_event_id = {$event->id} AND status = 3 GROUP BY character_id ORDER BY dmg DESC")->fetchAll(\PDO::FETCH_ASSOC);
            foreach($rows as $i => $row){
                if($row['character_id'] == $player->character->id){
                    $ranking = $i + 1;
                    break;
                }
            }
        }

        $this->_wbRanking = $ranking;
        $this->_wbEvent = $event;
        $this->_wbAttackCount = $ownAttackCount;
        $this->_wbAttacks = $attacks;

        foreach($attacks as $atk){
            if($atk->status == 1){
                $this->_wbPendingAttack = $atk;
                break;
            }
        }

        return [[
            'id' => $event->id,
            'identifier' => $event->identifier,
            'status' => $event->status,
            'stage' => $event->stage,
            'min_level' => $event->min_level,
            'max_level' => $event->max_level,
            'npc_identifier' => $event->npc_identifier,
            'npc_hitpoints_total' => $event->npc_hitpoints_total,
            'npc_hitpoints_current' => $event->npc_hitpoints_current,
            'attack_count' => $event->attack_count,
            'ts_end' => $event->ts_end,
            'top_attacker_name' => $event->top_attacker_name,
            'top_attacker_count' => $event->top_attacker_count,
            'winning_attacker_name' => $event->winning_attacker_name,
            'reward_top_rank_item_identifier' => $event->reward_top_rank_item_identifier,
            'reward_top_pool_item_identifier' => $event->reward_top_pool_item_identifier,
        ]];
    }

    private function getWorldbossCharacterData($player){
        if(!isset($this->_wbEvent)) return [];
        $event = $this->_wbEvent;
        $lvl = $player->getLVL();

        $coinBase = GameSettings::getConstant('worldboss_event_reward_coin_base') ?: 1;
        $coinDuration = GameSettings::getConstant('worldboss_event_reward_coin_duration') ?: 300;
        $coinFalloff = GameSettings::getConstant('worldboss_event_reward_coin_falloff') ?: 0.97;
        $xpBase = GameSettings::getConstant('worldboss_event_reward_xp_base') ?: 1;
        $xpDuration = GameSettings::getConstant('worldboss_event_reward_xp_duration') ?: 300;
        $xpFalloff = GameSettings::getConstant('worldboss_event_reward_xp_falloff') ?: 0.97;

        $coinTotal = 0;
        $xpTotal = 0;
        foreach($this->_wbAttacks as $atk){
            if($atk->status == 3){
                $coinTotal += floor(\Cls\Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, max(0, $atk->id - 1)));
                $xpTotal += floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, max(0, $atk->id - 1)));
            }
        }

        $nextCoin = floor(\Cls\Utils::coinsPerTime($lvl) * $coinBase * $coinDuration * pow($coinFalloff, $this->_wbAttackCount));
        $nextXp = floor(\Cls\Utils::coinsPerTime($lvl) * 1.5 * $xpBase * $xpDuration * pow($xpFalloff, $this->_wbAttackCount));

        return [[
            'worldboss_event_id' => $event->id,
            'ranking' => $this->_wbRanking,
            'coin_reward_total' => $coinTotal,
            'coin_reward_next_attack' => $nextCoin,
            'xp_reward_total' => $xpTotal,
            'xp_reward_next_attack' => $nextXp,
        ]];
    }
}