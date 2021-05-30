<?php

namespace App\Http\Models;

use Nevestul4o\NetworkController\Models\BaseModel;

class Country extends BaseModel
{
    const TABLE_NAME = 'countries';

    const F_NAME = 'name';

    protected $table = self::TABLE_NAME;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME
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

    protected $queryAble = [
        self::F_NAME => self::QUERYABLE_FULL_MATCH,
    ];
}
