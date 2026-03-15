<?php
namespace Request;

use Srv\Core;
use Schema\GuildBattleRewards;
use Schema\GuildBattle;

class claimGuildBattleReward{
    public function __request($player){
        $battleid = intval(getField('guild_battle_id', FIELD_NUM));

        $reward = GuildBattleRewards::find(function($q)use($battleid,$player){ $q->where('guild_battle_id',$battleid)->where('character_id',$player->character->id); });
        if(!$reward)
            return Core::setError('');

        $player->giveMoney($reward->game_currency);
        $reward->remove();

        if($reward->type == 3){
            $player->incrementGoalStat('guild_dungeons_fought');
            if($reward->game_currency > 0)
                $player->incrementGoalStat('guild_dungeons_won');
        }else{
            $player->incrementGoalStat('guild_battles_fought');
            $battle = GuildBattle::find(function($q)use($battleid){ $q->where('id',$battleid); });
            if($battle && $battle->guild_winner_id == $player->character->guild_id)
                $player->incrementGoalStat('guild_battles_won');
            else
                $player->incrementGoalStat('guild_battles_lost');
        }

        Core::req()->data = [
            'character'=>$player->character
        ];

        if($player->inventory->sidekick_id)
            Core::req()->data['sidekick'] = $player->sidekicks;
    }
}