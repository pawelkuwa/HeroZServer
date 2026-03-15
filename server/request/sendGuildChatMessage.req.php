<?php
namespace Request;

use Srv\Core;
use Srv\Socket;
use Schema\Character;

class sendGuildChatMessage{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');
            
        $message = getField('message');
        $message = Core::validMSG($message);
        $officer_message = getField("officer_message")=='true';
        $toCharacterName = getField('character_to_name',0,FALSE);
        
        $toCharacter = false;
        if($toCharacterName && strlen($toCharacterName) > 0)
        	$toCharacter = Character::find(function($q)use($toCharacterName){ $q->where('name',$toCharacterName); })->id;
        
		$guildMsg = $player->guild->sendMessage($player, $message, $officer_message, $toCharacter);

        Socket::syncGuildLog($player->character->guild_id, $player->user->id, [
            'timestamp' => $guildMsg->timestamp,
            'character_from_name' => $player->character->name,
            'message' => $message,
            'is_private' => $toCharacter ? 1 : 0,
            'is_officer' => $officer_message ? 1 : 0,
            'id' => $guildMsg->id,
        ]);

        Core::req()->data = array();
    }
}