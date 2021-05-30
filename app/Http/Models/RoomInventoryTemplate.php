<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\RoomInventoryTemplateTransformer;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Nevestul4o\NetworkController\Models\BaseModel;

class RoomInventoryTemplate extends BaseModel
{
    const TABLE_NAME = 'room_inventory_templates';

    const FR_CUSTOMER = 'customer';

    const F_CUSTOMER_ID = self::FR_CUSTOMER . '_' . self::F_ID;
    const F_NAME = 'name';
    const F_ROOM_ITEMS = 'room_items';

    protected $table = self::TABLE_NAME;

    protected $transformer = RoomInventoryTemplateTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
    ];

    protected $fillable = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
    ];

    protected $with = [
        self::F_ROOM_ITEMS,
    ];

    protected $dates = [
        self::F_CREATED_AT,
        self::F_UPDATED_AT,
        self::F_DELETED_AT,
    ];

    protected $casts = [
        self::F_CREATED_AT => self::CAST_TIMESTAMP,
    ];

    protected $queryAble = [
        self::F_NAME => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
    ];

    /**
     * @return HasManyThrough
     */
    public function room_items(): HasManyThrough
    {
        return $this->HasManyThrough(
            RoomItem::class,
            RoomInventoryTemplateItems::class,
            RoomInventoryTemplateItems::F_ROOM_INVENTORY_TEMPLATE_ID,
            RoomItem::F_ID,
            RoomItem::F_ID,
            RoomInventoryTemplateItems::F_ROOM_ITEM_ID
        );
    }
}
