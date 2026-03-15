<?php
namespace Request;

use Srv\Core;
use Schema\GuildInvites;

class declineGuildInvitation{
    public function __request($player){
        $guild_id = intval(getField('guild_id', FIELD_NUM));

        $invite = GuildInvites::find(function($q)use($guild_id, $player){
            $q->where('guild_id', $guild_id)->where('character_id', $player->character->id);
        });

        if(!$invite || (time() - $invite->ts_creation) >= 259200)
            return Core::setError('errDeclineGuildInvitationInvalidInvitation');

        $invite->remove();
    }
}
