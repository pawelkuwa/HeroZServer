<?php
namespace Request;

use Srv\Core;
use Srv\Config;

class releaseArtifact{
    public function __request($player){
        if($player->character->guild_id == 0)
            return Core::setError('errCharacterNoGuild');
        if($player->character->guild_rank > 2)
            return Core::setError('errReleaseArtifactNoPermission');

        $guild = $player->guild;
        $artifactId = intval(getField('id', FIELD_NUM));

        $cost = Config::get('constants.release_guild_artifact_cost', 10);
        if($player->getPremium() < $cost)
            return Core::setError('errRemovePremiumCurrencyNotEnough');

        $cooldown = Config::get('constants.release_guild_artifact_cooldown', 86400);
        if($guild->ts_last_artifact_released > 0 && (time() - $guild->ts_last_artifact_released) < $cooldown)
            return Core::setError('errReleaseArtifactActiveCooldown');

        $pending = $guild->getPendingAttack() || $guild->getPendingDefense();
        if($pending)
            return Core::setError('errReleaseArtifactActiveBattle');

        $artifacts = json_decode($guild->artifact_ids, true) ?: [];
        $idx = array_search($artifactId, $artifacts);
        if($idx === false)
            return Core::setError('');

        array_splice($artifacts, $idx, 1);
        $guild->artifact_ids = json_encode($artifacts);
        $guild->artifacts_owned_current = count($artifacts);
        $guild->ts_last_artifact_released = time();
        $player->givePremium(-$cost);

        Core::req()->data = [
            'guild' => $guild,
            'character' => $player->character
        ];
    }
}
