<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\CustomerContactTransformer;
use Nevestul4o\NetworkController\Models\BaseModel;

class CustomerContact extends BaseModel
{
    const TABLE_NAME = 'customer_contacts';

    const FR_CUSTOMER = 'customer';

    const F_CUSTOMER_ID = self::FR_CUSTOMER . '_' . self::F_ID;
    const F_TITLE = 'title';
    const F_NAME = 'name';
    const F_PHONE = 'phone';
    const F_EMAIL = 'email';

    const TITLE_MANAGER = 'M';
    const TITLE_FRONT_OFFICE_MANAGER = 'F';
    const TITLE_HOSTESS = 'H';
    const TITLE_OTHER = 'O';

    const TITLES = [
        'Мениджър'            => self::TITLE_MANAGER,
        'Фронт офис мениджър' => self::TITLE_FRONT_OFFICE_MANAGER,
        'Хостеса'             => self::TITLE_HOSTESS,
        'Друго'               => self::TITLE_OTHER,
    ];

    protected $table = self::TABLE_NAME;

    protected $transformer = CustomerContactTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
    ];

    protected $fillable = [
        self::F_CUSTOMER_ID,
        self::F_TITLE,
        self::F_NAME,
        self::F_PHONE,
        self::F_EMAIL,
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
        self::F_EMAIL => self::QUERYABLE_FULL_MATCH,
        self::F_PHONE => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_CUSTOMER_ID,
        self::F_NAME,
        self::F_PHONE,
        self::F_EMAIL,
    ];
}
