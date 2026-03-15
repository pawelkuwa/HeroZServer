<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class deleteUser{
    public function __request($player){
        $password = getField('password');

        if($password !== '-' && $player->user->password_hash != Core::passwordHash($password))
            return Core::setError('errDeleteUserInvalidPassword');

        if($player->character->guild_id != 0 && $player->character->guild_rank == 1)
            return Core::setError('errDeleteUserGuildLeader');

        $userId = $player->user->id;
        $charId = $player->character->id;

        if($player->character->guild_id != 0){
            $player->guild->removeMember($charId);
            $player->character->guild_id = 0;
            $player->character->guild_rank = 0;
        }

        DB::$connection->prepare("DELETE FROM items WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM inventory WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM bank_inventory WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM quests WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM training WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM work WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM duel WHERE character_a_id = ? OR character_b_id = ?")->execute([$charId, $charId]);
        DB::$connection->prepare("DELETE FROM dungeons WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM dungeon_quests WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM sidekicks WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM slotmachines WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM collected_goals WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM herobook_objectives WHERE character_id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM `character` WHERE id = ?")->execute([$charId]);
        DB::$connection->prepare("DELETE FROM user WHERE id = ?")->execute([$userId]);
    }
}
