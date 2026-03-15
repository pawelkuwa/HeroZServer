<?php
namespace Request;

use Srv\Core;
use Cls\Utils;
use Schema\GuildDungeon;
use Schema\GuildDungeonBattle;
use Cls\Utils\GuildLogType;

class attackGuildDungeonNPCTeam{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');
        if($player->character->guild_rank == 3)
            return Core::setError('errCreateNoPermission');

        $guild_dungeon_battle_id = intval(getField('guild_dungeon_battle_id', FIELD_NUM));

        $guild_dungeon = GuildDungeon::find(function($q)use($player, $guild_dungeon_battle_id){ $q->where('guild_id',$player->character->guild_id)->where('id',$guild_dungeon_battle_id); });

        $time = intval(getField('time', FIELD_NUM));

        if(($time < 1 || $time > 5))
            return Core::setError('errCreateInvalidGuild');

        $cost = Utils::guildBattleCost($player->guild->totalImprovementPercentage());
        if($player->guild->getMoney() < $cost)
            return Core::setError('errRemoveGameCurrencyNotEnough');

        $battle_ts = Utils::getGuildBattleAttackTimestamp($time);
        $dungeon_battle = new GuildDungeonBattle([
            'battle_time'=>$time,
            'ts_attack'=>$battle_ts,
            'guild_id'=>$player->guild->id,
            'npc_team_identifier'=>$guild_dungeon->npc_team_identifier,
            'status' => 2,
            'settings'=>$guild_dungeon->settings,
            'character_ids'=>'['.($player->character->id).']',
        ]);
        $dungeon_battle->save();

        $player->guild->addLog($player, GuildLogType::GuildDungeonBattle_Attack, $guild_dungeon->id, $guild_dungeon->npc_team_identifier, $battle_ts);
        $player->guild->pending_guild_dungeon_battle_attack_id = $dungeon_battle->id;

        Core::req()->data = array(
            'character'=>[],
            'guild'=>$player->guild,
            'pending_guild_dungeon_battle' => [
                "id" => $dungeon_battle->id,
                "battle_time" => $dungeon_battle->battle_time,
                "ts_attack" => $dungeon_battle->ts_attack,
                "guild_id" => $dungeon_battle->guild_id,
                "npc_team_identifier" => $dungeon_battle->npc_team_identifier,
                "npc_team_character_profiles" => $dungeon_battle->npc_team_character_profiles,
                "status" => $dungeon_battle->status,
                "settings" => $dungeon_battle->settings,
                "character_ids" => $dungeon_battle->character_ids
            ]
        );
    }
}
