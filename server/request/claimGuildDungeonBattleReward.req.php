<?php
namespace Request;

use Srv\Core;
use Schema\GuildBattleRewards;

class claimGuildDungeonBattleReward{
    public function __request($player){
        $battleid = intval(getField('guild_dungeon_battle_id', FIELD_NUM));
        $discard = getField('discard_item') === 'true';

        $reward = GuildBattleRewards::find(function($q)use($battleid, $player){
            $q->where('guild_battle_id', $battleid)->where('character_id', $player->character->id)->where('type', 3);
        });
        if(!$reward)
            return Core::setError('');

        $player->giveMoney($reward->game_currency);

        if($reward->item_id && !$discard){
            $slot = $player->getFreeBagSlot();
            if(!$slot)
                return Core::setError('errInventoryNoEmptySlot');
            $player->inventory->$slot = $reward->item_id;
        }

        $reward->remove();

        $player->character->finished_guild_dungeon_battle_id = 0;
        $player->incrementGoalStat('guild_dungeons_fought');
        if($reward->game_currency > 0)
            $player->incrementGoalStat('guild_dungeons_won');

        Core::req()->data = [
            'character' => $player->character
        ];
    }
}
