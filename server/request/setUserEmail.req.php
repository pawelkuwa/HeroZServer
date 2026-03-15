<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class setUserEmail{
    public function __request($player){
        $email = getField('email_new', FIELD_EMAIL);
        $password = getField('password');

        if(!$email)
            return Core::setError('errChangeEmailInvalidEmail');

        if($player->user->password_hash != Core::passwordHash($password))
            return Core::setError('errChangeEmailInvalidPassword');

        if($email == $player->user->email)
            return Core::setError('errChangeEmailSameEmail');

        $stmt = DB::$connection->prepare("SELECT id FROM user WHERE email = ? AND id != ?");
        $stmt->execute([$email, $player->user->id]);
        if($stmt->fetch())
            return Core::setError('errChangeEmailUserAlreadyExists');

        $player->user->email = $email;
    }
}
