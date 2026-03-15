<?php
namespace Request;

use Srv\Core;
use Srv\DB;

class getUserVoucher{
    public function __request($player){
        $id = intval(getField('id', FIELD_NUM));
        if(!$id)
            return Core::setError('');

        $v = DB::sql("SELECT id, code, rewards, ts_end FROM vouchers WHERE id = ? AND status = 1", [$id])->fetch(\PDO::FETCH_ASSOC);
        if(!$v){
            Core::req()->data = [];
            return;
        }

        if($v['ts_end'] > 0 && time() > $v['ts_end']){
            Core::req()->data = [];
            return;
        }

        $existing = DB::sql("SELECT id FROM voucher_redemptions WHERE voucher_id = ? AND user_id = ?", [$v['id'], $player->user->id])->fetch();
        if($existing){
            Core::req()->data = [];
            return;
        }

        Core::req()->data = [
            'voucher' => [
                'id' => (int)$v['id'],
                'code' => $v['code'],
                'ts_end' => (int)$v['ts_end'],
                'rewards' => $v['rewards']
            ]
        ];
    }
}
