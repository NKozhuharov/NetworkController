<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\CustomerTransformer;
use App\Observers\CustomerObserver;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseModel;

class Customer extends BaseModel
{
    const TABLE_NAME = 'customers';

    const FR_COUNTRY = 'country';
    const FR_USER = 'user';

    const F_NAME = 'name';
    const F_EMAIL = 'email';
    const F_COUNTRY_ID = self::FR_COUNTRY . '_' . self::F_ID;
    const F_TOWN = 'town';
    const F_ADDRESS = 'address';
    const F_USER_ID = self::FR_USER . '_' . self::F_ID;

    protected $table = self::TABLE_NAME;

    protected $transformer = CustomerTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_EMAIL,
        self::F_NAME,
        self::F_COUNTRY_ID,
        self::F_TOWN,
        self::F_ADDRESS,
    ];

    protected $fillable = [
        self::F_NAME,
        self::F_EMAIL,
        self::F_COUNTRY_ID,
        self::F_TOWN,
        self::F_ADDRESS,
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
        self::F_NAME    => self::QUERYABLE_FULL_MATCH,
        self::F_EMAIL   => self::QUERYABLE_FULL_MATCH,
        self::F_TOWN    => self::QUERYABLE_FULL_MATCH,
        self::F_ADDRESS => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_NAME,
        self::F_EMAIL,
        self::F_COUNTRY_ID,
        self::F_TOWN,
        self::F_ADDRESS,
        self::F_USER_ID,
    ];

    protected $resolveAble = [
        self::FR_USER,
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(new CustomerObserver());
    }

    /**
     * @return HasOne
     */
    public function user(): HasOne
    {
        return $this->HasOne(
            User::class,
            BaseModel::F_ID,
            self::F_USER_ID
        );
    }
}
