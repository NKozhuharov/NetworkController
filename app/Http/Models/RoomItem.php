<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\RoomItemTransformer;
use Nevestul4o\NetworkController\Models\BaseModel;

class RoomItem extends BaseModel
{
    const TABLE_NAME = 'room_items';

    const F_NAME = 'name';

    protected $table = self::TABLE_NAME;

    protected $transformer = RoomItemTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
    ];

    protected $fillable = [
        self::F_NAME,
    ];

    protected $dates = [
        self::F_CREATED_AT,
        self::F_UPDATED_AT,
        self::F_DELETED_AT,
    ];

    protected $casts = [
        self::F_CREATED_AT => self::CAST_TIMESTAMP,
    ];

    protected $filterAble = [
        self::F_NAME,
    ];

    protected $queryAble = [
        self::F_NAME => self::QUERYABLE_FULL_MATCH,
    ];
}
