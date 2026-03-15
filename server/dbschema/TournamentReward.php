<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class TournamentReward extends Record implements JsonSerializable{
    protected static $_TABLE = 'tournament_rewards';

    public function jsonSerialize(){
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id'=>0,
        'tournament_id'=>0,
        'character_id'=>0,
        'week'=>0,
        'rewards'=>'',
        'claimed'=>0,
    ];
}
