<?php
namespace Request;

use Srv\Core;
use Schema\WorldbossAttack;

class abortWorldbossAttack{
    public function __request($player){
        $attack = WorldbossAttack::find(function($q) use($player){
            $q->where('character_id', $player->character->id)->where('status', 1);
        });
        if(!$attack)
            return Core::setError('errAbortWorldbossAttackNoActiveWorldbossAttack');

        $attack->status = 2;

        $player->character->active_worldboss_attack_id = 0;

        Core::req()->data = [
            'character' => $player->character,
            'worldboss_attack' => $attack,
        ];
    }
}
