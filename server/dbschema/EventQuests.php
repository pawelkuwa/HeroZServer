<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class EventQuests extends Record implements JsonSerializable{
    protected static $_TABLE = 'event_quests';

    public function jsonSerialize() {
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id' => 0,
        'character_id' => 0,
        'identifier' => '',
        'status' => 1,
        'end_date' => '',
        'objective1_value' => 0,
        'objective2_value' => 0,
        'objective3_value' => 0,
        'objective4_value' => 0,
        'objective5_value' => 0,
        'objective6_value' => 0,
        'rewards' => '',
        'reward_item1_id' => 0,
        'reward_item2_id' => 0,
        'reward_item3_id' => 0,
        'ts_creation' => 0,
    ];
}
