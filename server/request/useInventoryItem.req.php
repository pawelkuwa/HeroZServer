<?php
namespace Request;

use Srv\Core;
use Cls\Utils;
use Cls\Utils\Item;
use Cls\Utils\ItemsList;
use Cls\GameSettings;
use Schema\Sidekicks;
use Schema\Items;

class useInventoryItem{
    public function __request($player){
        $itemid = intval(getField('item_id',FIELD_NUM));
        if(!$itemid)
            return Core::setError('errInvItem');
        $item = $player->getItemById($itemid);
        $itemslotname = $player->inventory->getSlotByItemId($item->id);
        if(!$item)
            return Core::setError('errInvItem');
        if(!in_array($item->type, Item::$USABLE))
            return Core::setError('errNotUsable');

        switch(Item::$TYPE[$item->type]){
            case 'sidekick':

                $skills = randomSidekickSkills();
                $q = new Sidekicks([
                    'character_id'=>$player->character->id,
                    'identifier'=>$item->identifier,
                    'quality'=>$item->quality,
                    'stat_base_stamina'=>$item->stat_stamina,
                    'stat_base_strength'=>$item->stat_strength,
                    'stat_base_critical_rating'=>$item->stat_critical_rating,
                    'stat_base_dodge_rating'=>$item->stat_dodge_rating,
                    'stat_stamina'=>$item->stat_stamina,
                    'stat_strength'=>$item->stat_strength,
                    'stat_critical_rating'=>$item->stat_critical_rating,
                    'stat_dodge_rating'=>$item->stat_dodge_rating,
                    'stage1_skill_id'=>$skills[0],
                    'stage2_skill_id'=>$skills[1],
                    'stage3_skill_id'=>$skills[2]
                ]);

                $q->save();
                $item->remove();
                $player->setItemInInventory(null, $itemslotname);

                $sidekick_data = json_decode($player->inventory->sidekick_data)->orders;

                $sidekick_data[] = $q->id;

                $player->inventory->sidekick_data = json_encode(array("orders" => $sidekick_data));

                $player->incrementGoalStat('sidekick_collected');
                $existingCount = Sidekicks::findAll(function($q) use($player, $item) {
                    $q->where('character_id', $player->character->id)->where('identifier', $item->identifier);
                });
                if(count($existingCount) <= 1)
                    $player->incrementGoalStat('different_sidekick_collected');

                Core::req()->data = [
                    'character'=>$player->character,
                    'inventory'=>['id'=>$player->inventory->id, $itemslotname=>0]
                ];
            break;

            case 'reskill':
                $strength = intval(getField('strength',FIELD_NUM));
                $stamina = intval(getField('stamina',FIELD_NUM));
                $critical_rating = intval(getField('critical_rating',FIELD_NUM));
                $dodge_rating = intval(getField('dodge_rating',FIELD_NUM));
                $totalReskill = $strength + $stamina + $critical_rating + $dodge_rating;
                $totalCharacter = $player->character->stat_points_available + $player->character->stat_base_strength + $player->character->stat_base_stamina + $player->character->stat_base_critical_rating + $player->character->stat_base_dodge_rating;
                $freeStats = $totalCharacter - $totalReskill;
                if($freeStats < 0)
                    return Core::setError('errItsWrongBybe');
                $player->character->stat_points_available = $freeStats;
                $player->character->stat_base_strength = $strength;
                $player->character->stat_base_stamina = $stamina;
                $player->character->stat_base_critical_rating = $critical_rating;
                $player->character->stat_base_dodge_rating = $dodge_rating;
                $player->calculateStats();
                $item->remove();
                $player->setItemInInventory(null, $itemslotname);

                Core::req()->data = [
                    'character'=>$player->character,
                    'inventory'=>['id'=>$player->inventory->id, $itemslotname=>0]
                ];
            break;

            case 'surprise':
                $numItems = mt_rand(1, 3);
                $freeSlots = [];
                for($i = 0; $i < $numItems; $i++){
                    $slot = $player->findEmptyInventorySlot();
                    if($slot === null){
                        if($i == 0)
                            return Core::setError('errInventoryNoEmptySlot');
                        break;
                    }
                    $freeSlots[] = $slot;
                    $player->inventory->{$slot} = -1;
                }

                $item->remove();
                $player->setItemInInventory(null, $itemslotname);

                $lvl = $player->getLVL();
                $newItems = [];
                $invUpdate = ['id' => $player->inventory->id, $itemslotname => 0];

                foreach($freeSlots as $slot){
                    $type = mt_rand(1, 5);
                    $quality = mt_rand(2, 3);
                    $typeName = Item::$TYPE[$type];
                    $itemPool = ItemsList::$ITEMS[$typeName] ?? [];
                    if(empty($itemPool)){
                        $player->inventory->{$slot} = 0;
                        continue;
                    }

                    $candidates = array_filter($itemPool, function($it) use($quality, $lvl){
                        return $it['quality'] == $quality && $it['required_level'] <= $lvl;
                    });
                    if(empty($candidates)){
                        $candidates = array_filter($itemPool, function($it) use($lvl){
                            return $it['required_level'] <= $lvl;
                        });
                    }
                    if(empty($candidates)){
                        $player->inventory->{$slot} = 0;
                        continue;
                    }

                    $candidates = array_values($candidates);
                    $picked = $candidates[mt_rand(0, count($candidates) - 1)];

                    $newItem = $player->createItem($picked);
                    $newItem = Utils::randomiseItem($newItem, $lvl);
                    if($type == Item::$TYPE_ID['weapon'] ?? 6)
                        $newItem->stat_weapon_damage = round($newItem->item_level * GameSettings::getConstant('item_weapon_damage_factor'));
                    else
                        $newItem->stat_weapon_damage = 0;

                    $player->setItemInInventory($newItem, $slot);
                    $invUpdate[$slot] = $newItem->id;
                    $newItems[] = ['id' => $newItem->id];

                    Utils::addItemToOwnedTemplates($player, $newItem);
                    Utils::addItemToPattern($player, $newItem);
                }

                $player->incrementGoalStat('surprise_box_opened');

                Core::req()->data = [
                    'character' => $player->character,
                    'inventory' => $invUpdate,
                    'items' => $player->items,
                ];
            break;
        }
    }
}
