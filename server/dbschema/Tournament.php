<?php
namespace Schema;

use Srv\Record;
use JsonSerializable;

class Tournament extends Record implements JsonSerializable{
    protected static $_TABLE = 'tournaments';

    public function jsonSerialize(){
        return $this->getData();
    }

    protected static $_FIELDS = [
        'id'=>0,
        'week'=>0,
        'ts_start'=>0,
        'ts_end'=>0,
        'status'=>0,
    ];
}
