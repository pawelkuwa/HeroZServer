<?php
namespace Request;

use Srv\Core;
use Cls\GameSettings;
use Schema\WorldbossAttack;

class instantFinishWorldbossAttack{
    public function __request($player){
        $attack = WorldbossAttack::find(function($q) use($player){
            $q->where('character_id', $player->character->id)->where('status', 1);
        });
        if(!$attack)
            return Core::setError('errInstantFinishWorldbossAttackNoActiveWorldbossAttack');

        $cost = GameSettings::getConstant('worldboss_event_instant_finish_premium_amount') ?: 2;
        if($player->getPremium() < $cost)
            return Core::setError('errRemovePremiumCurrencyNotEnough');

        $player->givePremium(-$cost);
        $attack->ts_complete = 0;

        Core::req()->data = [
            'user' => $player->user,
            'character' => $player->character,
            'worldboss_attack' => $attack,
        ];
    }
}
