<?php
namespace Request;

use Srv\Core;
use Schema\GuildBattleRewards;
use Cls\Utils\GuildLogType;

class joinGuildDungeonBattle{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');

        $attack = getField('attack', FIELD_BOOL) == 'true';

        $pending = $player->guild->getPendingDungeon();
        if($pending == null)
            return Core::setError('errJoinGuildBattleInvalidGuildBattle');

        if(GuildBattleRewards::find(function($q)use($pending,$player){ $q->where('guild_battle_id',$pending->battle->id)->where('character_id',$player->character->id); }))
            return Core::setError('errJoinGuildBattleAlreadyFought');

        if(!$pending->battle->addPlayerToDungeonBattle($player))
            return Core::setError('errAddCharacterIdAlreadyJoined');
        $player->guild->addLog($player, GuildLogType::GuildDungeonBattle_Joined);

        Core::req()->data = array(
            "pending_guild_dungeon_battle"=>$pending->battle->getGuildDungeon()
        );
    }
}
