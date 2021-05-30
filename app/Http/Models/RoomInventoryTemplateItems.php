<?php

namespace App\Http\Models;
use Nevestul4o\NetworkController\Models\BaseModel;

class RoomInventoryTemplateItems extends BaseModel
{
    const TABLE_NAME = 'room_inventory_template_items';
    const F_ROOM_INVENTORY_TEMPLATE_ID = RoomInventoryTemplate::TABLE_NAME . '_' . RoomInventoryTemplate::F_ID;
    const F_ROOM_ITEM_ID = RoomItem::TABLE_NAME . '_' . RoomItem::F_ID;
}
