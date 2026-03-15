<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class redeemVoucher{
    public function __request($player){
        $code = trim(getField('code'));
        if(empty($code))
            return Core::setError('errRedeemVoucherInvalidCode');

        $voucher = DB::sql("SELECT * FROM vouchers WHERE code = ? AND status = 1", [$code])->fetch(\PDO::FETCH_ASSOC);
        if(!$voucher)
            return Core::setError('errRedeemVoucherInvalidCode');

        $now = time();

        if($voucher['ts_start'] > 0 && $now < $voucher['ts_start'])
            return Core::setError('errRedeemInactiveCampaign');
        if($voucher['ts_end'] > 0 && $now > $voucher['ts_end'])
            return Core::setError('errRedeemInactiveVoucher');

        if($voucher['uses_max'] > 0 && $voucher['uses_current'] >= $voucher['uses_max'])
            return Core::setError('errRedeemLimitReached');

        if($voucher['user_id'] > 0 && $voucher['user_id'] != $player->user->id)
            return Core::setError('errCheckVoucherRequirementsWrongUser');

        if($voucher['min_level'] > 0 && $player->character->level < $voucher['min_level'])
            return Core::setError('errCheckCampaignRequirementsInvalidLevel');

        if(!empty($voucher['locale']) && $voucher['locale'] != $player->user->locale)
            return Core::setError('errCheckCampaignRequirementsInvalidLocale');

        $existing = DB::sql("SELECT id FROM voucher_redemptions WHERE voucher_id = ? AND user_id = ?", [$voucher['id'], $player->user->id])->fetch();
        if($existing)
            return Core::setError('errInvalidateAlreadyRedeemed');

        // Redeem
        DB::sql("INSERT INTO voucher_redemptions (voucher_id, user_id, character_id, ts_redeemed) VALUES (?, ?, ?, ?)", [$voucher['id'], $player->user->id, $player->character->id, $now]);
        DB::sql("UPDATE vouchers SET uses_current = uses_current + 1 WHERE id = ?", [$voucher['id']]);

        $rewards = json_decode($voucher['rewards'], true) ?: [];
        $voucherRewards = [];

        if(!empty($rewards['game_currency'])){
            $player->giveMoney($rewards['game_currency']);
            $voucherRewards['game_currency'] = (int)$rewards['game_currency'];
        }
        if(!empty($rewards['premium_currency'])){
            $player->user->premium_currency += $rewards['premium_currency'];
            $voucherRewards['premium_currency'] = (int)$rewards['premium_currency'];
        }
        if(!empty($rewards['stat_points'])){
            $player->character->stat_points_available += $rewards['stat_points'];
            $voucherRewards['stat_points'] = (int)$rewards['stat_points'];
        }
        if(!empty($rewards['quest_energy'])){
            $player->character->quest_energy += $rewards['quest_energy'];
            $voucherRewards['quest_energy'] = (int)$rewards['quest_energy'];
        }
        if(!empty($rewards['training_sessions'])){
            $player->character->training_count += $rewards['training_sessions'];
            $voucherRewards['training_sessions'] = (int)$rewards['training_sessions'];
        }

        // Boosters
        $boosterTypes = ['quest_booster','stats_booster','work_booster','sense_booster','league_booster'];
        foreach($boosterTypes as $bt){
            if(!empty($rewards[$bt])){
                $voucherRewards[$bt] = (int)$rewards[$bt];
            }
        }

        // Items
        if(!empty($rewards['items']) && is_array($rewards['items'])){
            $itemResults = [];
            foreach($rewards['items'] as $itemData){
                $item = $player->createItem($itemData);
                if($item) $itemResults[] = $item->toArray();
            }
            if(!empty($itemResults))
                $voucherRewards['items'] = $itemResults;
        }

        // Remove from new_user_voucher_ids if present
        if(!empty($player->character->new_user_voucher_ids)){
            $ids = json_decode($player->character->new_user_voucher_ids, true) ?: [];
            $ids = array_values(array_diff($ids, [(int)$voucher['id']]));
            $player->character->new_user_voucher_ids = !empty($ids) ? json_encode($ids) : '';
        }

        Core::req()->data = [
            'user' => $player->user,
            'character' => $player->character,
            'voucher_rewards' => $voucherRewards
        ];
    }
}
