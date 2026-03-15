<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class redeemUserVoucherLater{
    public function __request($player){
        $code = trim(getField('code'));
        if(empty($code))
            return Core::setError('');

        $voucher = DB::sql("SELECT id FROM vouchers WHERE code = ? AND status = 1", [$code])->fetch(\PDO::FETCH_ASSOC);
        if(!$voucher)
            return Core::setError('');

        $voucherId = (int)$voucher['id'];
        $ids = json_decode($player->character->new_user_voucher_ids, true) ?: [];
        if(!in_array($voucherId, $ids)){
            $ids[] = $voucherId;
            $player->character->new_user_voucher_ids = json_encode($ids);
        }

        Core::req()->data = [
            'character' => $player->character
        ];
    }
}
