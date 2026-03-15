<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class TournamentSnapshot extends Record implements JsonSerializable{
    protected static $_TABLE = 'tournament_snapshots';

    public function jsonSerialize(){
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id'=>0,
        'tournament_id'=>0,
        'character_id'=>0,
        'guild_id'=>0,
        'xp_start'=>0,
        'honor_start'=>0,
        'guild_honor_start'=>0,
    ];
}
