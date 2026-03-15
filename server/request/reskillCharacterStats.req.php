<?php
namespace Request;

use Srv\Core;
use Srv\Config;

class reskillCharacterStats{
    public function __request($player){
        $stamina = intval(getField('stamina', FIELD_NUM));
        $strength = intval(getField('strength', FIELD_NUM));
        $critical = intval(getField('critical_rating', FIELD_NUM));
        $dodge = intval(getField('dodge_rating', FIELD_NUM));

        $baseStat = Config::get('constants.init_base_stat_value', 10);
        if($stamina < $baseStat || $strength < $baseStat || $critical < $baseStat || $dodge < $baseStat)
            return Core::setError('errReskillInvalidStats');

        $oldTotal = $player->character->stat_base_stamina
                  + $player->character->stat_base_strength
                  + $player->character->stat_base_critical_rating
                  + $player->character->stat_base_dodge_rating
                  + $player->character->stat_points_available;

        $newTotal = $stamina + $strength + $critical + $dodge;

        if($newTotal != $oldTotal)
            return Core::setError('errReskillInvalidStats');

        $player->character->stat_base_stamina = $stamina;
        $player->character->stat_base_strength = $strength;
        $player->character->stat_base_critical_rating = $critical;
        $player->character->stat_base_dodge_rating = $dodge;
        $player->character->stat_points_available = 0;

        $player->calculateStats();

        Core::req()->data = [
            'character' => $player->character
        ];
    }
}
