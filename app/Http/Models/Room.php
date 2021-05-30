<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\RoomTransformer;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseModel;

class Room extends BaseModel
{
    const TABLE_NAME = 'rooms';

    const FR_CUSTOMER = 'customer';
    const FR_ROOM_TYPE = 'room_type';
    const FR_ROOM_INVENTORY_TEMPLATE = 'room_inventory_template';

    const F_CUSTOMER_ID = self::FR_CUSTOMER . '_' . self::F_ID;
    const F_ROOM_TYPE_ID = self::FR_ROOM_TYPE . '_' . self::F_ID;
    const F_ROOM_INVENTORY_TEMPLATE_ID = self::FR_ROOM_INVENTORY_TEMPLATE . '_' . self::F_ID;
    const F_NAME = 'name';
    const F_FLOOR = 'floor';

    protected $table = self::TABLE_NAME;

    protected $transformer = RoomTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_CUSTOMER_ID,
        self::F_ROOM_TYPE_ID,
        self::F_ROOM_INVENTORY_TEMPLATE_ID,
        self::F_NAME,
        self::F_FLOOR,
    ];

    protected $fillable = [
        self::F_CUSTOMER_ID,
        self::F_ROOM_TYPE_ID,
        self::F_ROOM_INVENTORY_TEMPLATE_ID,
        self::F_NAME,
        self::F_FLOOR,
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
        self::F_NAME  => self::QUERYABLE_FULL_MATCH,
        self::F_FLOOR => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_CUSTOMER_ID,
        self::F_ROOM_TYPE_ID,
        self::F_ROOM_INVENTORY_TEMPLATE_ID,
        self::F_NAME,
        self::F_FLOOR,
    ];

    protected $resolveAble = [
        self::FR_ROOM_TYPE,
        self::FR_ROOM_INVENTORY_TEMPLATE,
    ];

    /**
     * @return HasOne
     */
    public function room_type(): HasOne
    {
        return $this->HasOne(
            RoomType::class,
            RoomType::F_ID,
            self::F_ROOM_TYPE_ID
        );
    }

    /**
     * @return HasOne
     */
    public function room_inventory_template(): HasOne
    {
        return $this->HasOne(
            RoomInventoryTemplate::class,
            RoomInventoryTemplate::F_ID,
            self::F_ROOM_INVENTORY_TEMPLATE_ID
        );
    }
}
