<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class WorldbossReward extends Record implements JsonSerializable{
    protected static $_TABLE = 'worldboss_reward';

    public function jsonSerialize(){
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id' => 0,
        'worldboss_event_id' => 0,
        'character_id' => 0,
        'game_currency' => 0,
        'xp' => 0,
        'item_id' => 0,
        'sidekick_item_id' => 0,
        'quest_energy' => 0,
        'training_sessions' => 0,
        'rewards' => '',
    ];
}
