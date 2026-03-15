<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class HerobookObjectives extends Record implements JsonSerializable{
    protected static $_TABLE = 'herobook_objectives';

    public function jsonSerialize() {
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id' => 0,
        'character_id' => 0,
        'identifier' => '',
        'type' => 0,
        'duration_type' => 1,
        'status' => 1,
        'current_value' => 0,
        'max_value' => 0,
        'ts_end' => 0,
        'objective_index' => 0,
        'rewards' => '',
    ];
}
