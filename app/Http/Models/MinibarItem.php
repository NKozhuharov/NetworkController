<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\MinibarItemTransformer;
use Nevestul4o\NetworkController\Models\BaseModel;

class MinibarItem extends BaseModel
{
    const TABLE_NAME = 'minibar_items';

    const FR_CUSTOMER = 'customer';

    const F_CUSTOMER_ID = self::FR_CUSTOMER . '_' . self::F_ID;
    const F_NAME = 'name';
    const F_AMOUNT = 'amount';
    const F_PICTURE = 'picture';

    protected $table = self::TABLE_NAME;

    protected $transformer = MinibarItemTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
    ];

    protected $fillable = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
        self::F_PICTURE,
    ];

    protected $dates = [
        self::F_CREATED_AT,
        self::F_UPDATED_AT,
        self::F_DELETED_AT,
    ];

    protected $casts = [
        self::F_CREATED_AT => self::CAST_TIMESTAMP,
        self::F_PICTURE    => self::CAST_ARRAY,
    ];

    protected $queryAble = [
        self::F_NAME => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
        self::F_AMOUNT,
    ];
}
