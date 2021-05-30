<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\RoomTypeTransformer;
use App\Observers\RoomTypeObserver;
use Nevestul4o\NetworkController\Models\BaseModel;

class RoomType extends BaseModel
{
    const TABLE_NAME = 'room_types';

    const FR_CUSTOMER = 'customer';

    const F_CUSTOMER_ID = self::FR_CUSTOMER . '_' . self::F_ID;
    const F_NAME = 'name';

    protected $table = self::TABLE_NAME;

    protected $transformer = RoomTypeTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
    ];

    protected $fillable = [
        self::F_CUSTOMER_ID,
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

    protected $queryAble = [
        self::F_NAME => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(new RoomTypeObserver());
    }
}
