<?php
namespace Request;

use Srv\Core;
use Srv\DB;
use Srv\Config;
use Srv\Mail;
use Srv\Socket;
use Schema\Character;
use Schema\Messages;
use Schema\GuildInvites;
use Cls\Utils\MessageFlag;

class inviteToGuild{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');
        if($player->character->guild_rank == 3)
            return Core::setError('errInviteCharacterNoPermission');
        
        $target = getField("name", FIELD_ALNUM);
		$msg = getField("message");
		$msg = Core::validMSG($msg);
		
		if($player->character->name == $target)
		    return Core::setError('errInviteCharacterNoSelfInvite');
		    
		if(!$target || ctype_digit($target))
		    return Core::setError('errInviteToGuildInvalidCharacter_'.$target);
		    
		$invPlayer = Character::find(function($q)use($target){ $q->where('name',$target); });
		
		if(!$invPlayer)
		    return Core::setError('errInviteToGuildInvalidCharacter_'.$target);
		
		    
		if($invPlayer->guild_id == $player->character->guild_id)
		    return Core::setError('errInviteCharacterAlreadyMember');
		
		if($invPlayer->level < 2)
		    return Core::setError('errCreateSystemMessageInvalidRecipientLevel');
		
		$messageConv = "{$player->character->name} invites you to the team „{$player->guild->name}”.
Do you want to join this team?";
		
		if(strlen($msg) > 0)
			$messageConv .= "
		
Comment from the inviter:
$msg";

        $guildInvite = new GuildInvites([
        	'character_id'=>$invPlayer->id,
        	'guild_id'=>$player->guild->id,
        	'ts_creation'=>time()
        ]);
        $message = new Messages([
        	'character_from_id'=>$player->character->id,
        	'character_to_ids'=>";{$invPlayer->id};",
        	'subject'=>"Invitation to the team „{$player->guild->name}”",
        	'message'=>$messageConv,
        	'flag'=>MessageFlag::FLAG_GUILD_INVITATION,
        	'flag_value'=>$player->character->guild_id,
        	'ts_creation'=>time()
        ]);
        $message->save();

        Socket::syncGame($invPlayer->user_id);

        // Email notification
        if(Config::get('email.notify_guild_invite')){
            $stmt = DB::$connection->prepare("SELECT u.email, u.email_notifications, u.locale FROM user u JOIN `character` c ON c.user_id = u.id WHERE c.id = ?");
            $stmt->execute([$invPlayer->id]);
            $inv = $stmt->fetch(\PDO::FETCH_ASSOC);
            if($inv && $inv['email_notifications'])
                Mail::queue(0, $inv['email'], 'Guild Invitation — HeroZero', 'guild-invite', [
                    'guild_name'=>$player->guild->name,
                    'inviter_name'=>$player->character->name,
                    'locale'=>$inv['locale']
                ]);
        }
    }
}