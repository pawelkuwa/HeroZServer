<?php
namespace Request;

use Srv\Core;
use Cls\Utils;
use Cls\GameSettings;
use Srv\DB;
use Schema\Items;
use Schema\PatternItems;

class collectItemPatternReward{

    public function __request($player){
        $identifier = getField('identifier');
        $value = getField('value');

        $date = date('Y-m-d H:i:s');

        $itemPattern = GameSettings::getConstant("item_pattern.{$identifier}");
        if(!$itemPattern)
            return Core::setError('errInvalidItemPatternIdentifier');

        $collected = json_decode($player->character->collected_item_pattern ?? '[]', true) ?? [];
        foreach($collected as $data){
            foreach($data as $k => $v){
                if($k == $identifier && $v['value'] == $value)
                    return Core::setError('errCollectItemPatternAlreadyExists');
            }
        }

        $xd = $itemPattern['values'][$value] ?? null;
        if(!$xd) return Core::setError('errInvalidItemPatternValue');

        $itemSlot = null;
        $newItem = null;

        switch($xd['reward_type']){
            case 1: // StatPoint
                $base = GameSettings::getConstant('item_pattern_reward_stat_points_base') ?: 1;
                $player->character->stat_points_available += round($xd['reward_factor'] * $base);
                break;

            case 2: // Item
                $freeSlot = $player->findEmptyInventorySlot();
                if($freeSlot === null)
                    return Core::setError('errInventoryNoEmptySlot');

                $patternItem = PatternItems::find(function($q) use($identifier, $player){
                    $q->where('pattern_identifier', $identifier)
                      ->where('character_id', $player->character->id);
                });

                if(!$patternItem)
                    return Core::setError('errPatternItemNotFound');

                $newItem = new Items([
                    'character_id' => $player->character->id,
                    'identifier' => $patternItem->identifier,
                    'type' => $patternItem->type,
                    'quality' => $patternItem->quality,
                    'required_level' => $patternItem->required_level,
                    'charges' => $patternItem->charges,
                    'item_level' => $patternItem->item_level,
                    'ts_availability_start' => $patternItem->ts_availability_start,
                    'ts_availability_end' => $patternItem->ts_availability_end,
                    'premium_item' => $patternItem->premium_item,
                    'buy_price' => $patternItem->buy_price,
                    'sell_price' => $patternItem->sell_price,
                    'stat_stamina' => $patternItem->stat_stamina,
                    'stat_strength' => $patternItem->stat_strength,
                    'stat_critical_rating' => $patternItem->stat_critical_rating,
                    'stat_dodge_rating' => $patternItem->stat_dodge_rating,
                    'stat_weapon_damage' => $patternItem->stat_weapon_damage
                ]);
                $newItem->save();
                $player->items[] = $newItem;
                $player->inventory->{$freeSlot} = $newItem->id;
                $itemSlot = $freeSlot;

                Utils::addItemToOwnedTemplates($player, $newItem);

                PatternItems::delete(function($q) use($patternItem){
                    $q->where('id', $patternItem->id);
                });
                break;

            case 3: // Training_Sessions
                $base = GameSettings::getConstant('item_pattern_reward_training_base') ?: 1;
                $player->character->training_count += round($xd['reward_factor'] * $base);
                break;

            case 4: // Quest_Energy
                $base = GameSettings::getConstant('item_pattern_reward_energy_base') ?: 1;
                $player->character->quest_energy += round($xd['reward_factor'] * $base);
                break;
        }

        $collected[] = [$identifier => ['value' => intval($value), 'date' => $date]];

        $collectedJson = json_encode($collected);
        $player->character->collected_item_pattern = $collectedJson;
        DB::sql("UPDATE `character` SET `collected_item_pattern` = ? WHERE `id` = ?", [
            $collectedJson,
            $player->character->id
        ]);

        Core::req()->data = [
            'user' => $player->user,
            'character' => $player->character,
            'inventory' => $player->inventory,
            'items' => $player->items,
            'collected_item_pattern' => [
                [$identifier => ['value' => intval($value), 'date' => $date]]
            ]
        ];
    }
}
