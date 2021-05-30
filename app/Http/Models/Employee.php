<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\EmployeeTransformer;
use App\Observers\EmployeeObserver;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseModel;

class Employee extends BaseModel
{
    const TABLE_NAME = 'employees';

    const FR_COUNTRY = 'country';
    const FR_USER = 'user';

    const F_NAME = 'name';
    const F_SECOND_NAME = 'second_name';
    const F_SURNAME = 'surname';
    const F_EGN = 'egn';
    const F_ID_NUMBER = 'id_number';
    const F_GENDER = 'gender';
    const F_DATE_OF_BIRTH = 'date_of_birth';
    const F_COUNTRY_ID = self::FR_COUNTRY . '_' . self::F_ID;
    const F_ADDRESS = 'address';
    const F_ID_ISSUED_ON = 'id_issued_on';
    const F_ID_EXPIRES_ON = 'id_expires_on';
    const F_PHONE = 'phone';
    const F_EMAIL = 'email';
    const F_TYPE = 'type';
    const F_STATUS = 'status';
    const F_FILES = 'files';
    const F_USER_ID = self::FR_USER . '_' . self::F_ID;

    const GENDER_MALE = 'M';
    const GENDER_FEMALE = 'F';

    const TYPE_VALET = 'V';
    const TYPE_SENIOR_VALET = 'S';

    const STATUS_ACTIVE = 'A';
    const STATUS_CANCELLED = 'C';
    const STATUS_PAID_VACATION = 'P';
    const STATUS_UNPAID_VACATION = 'U';
    const STATUS_FIRED = 'F';

    const GENDERS = [
        'Мъж'  => self::GENDER_MALE,
        'Жена' => self::GENDER_FEMALE,
    ];

    const TYPES = [
        'Камериер'        => self::TYPE_VALET,
        'Старши Камериер' => self::TYPE_SENIOR_VALET,
    ];

    const STATUSES = [
        'Активен'         => self::STATUS_ACTIVE,
        'Прекратен'       => self::STATUS_CANCELLED,
        'Платен отпуск'   => self::STATUS_PAID_VACATION,
        'Неплатен отпуск' => self::STATUS_UNPAID_VACATION,
        'Уволнен'         => self::STATUS_FIRED,
    ];

    protected $table = self::TABLE_NAME;

    protected $transformer = EmployeeTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_NAME,
        self::F_SECOND_NAME,
        self::F_SURNAME,
        self::F_GENDER,
        self::F_COUNTRY_ID,
    ];

    protected $fillable = [
        self::F_NAME,
        self::F_SECOND_NAME,
        self::F_SURNAME,
        self::F_EGN,
        self::F_ID_NUMBER,
        self::F_GENDER,
        self::F_DATE_OF_BIRTH,
        self::F_COUNTRY_ID,
        self::F_ADDRESS,
        self::F_ID_ISSUED_ON,
        self::F_ID_EXPIRES_ON,
        self::F_PHONE,
        self::F_EMAIL,
        self::F_TYPE,
        self::F_STATUS,
        self::F_FILES,
    ];

    protected $dates = [
        self::F_CREATED_AT,
        self::F_UPDATED_AT,
        self::F_DELETED_AT,
    ];

    protected $casts = [
        self::F_CREATED_AT => self::CAST_TIMESTAMP,
        self::F_FILES      => self::CAST_ARRAY,
    ];

    protected $queryAble = [
        self::F_NAME        => self::QUERYABLE_FULL_MATCH,
        self::F_SECOND_NAME => self::QUERYABLE_FULL_MATCH,
        self::F_SURNAME     => self::QUERYABLE_FULL_MATCH,
        self::F_ID_NUMBER   => self::QUERYABLE_FULL_MATCH,
        self::F_EMAIL       => self::QUERYABLE_FULL_MATCH,
        self::F_EGN         => self::QUERYABLE_FULL_MATCH,
        self::F_ADDRESS     => self::QUERYABLE_FULL_MATCH,
        self::F_PHONE       => self::QUERYABLE_FULL_MATCH,
    ];

    protected $filterAble = [
        self::F_NAME,
        self::F_SECOND_NAME,
        self::F_SURNAME,
        self::F_EGN,
        self::F_ID_NUMBER,
        self::F_GENDER,
        self::F_DATE_OF_BIRTH,
        self::F_COUNTRY_ID,
        self::F_ADDRESS,
        self::F_ID_ISSUED_ON,
        self::F_ID_EXPIRES_ON,
        self::F_PHONE,
        self::F_EMAIL,
        self::F_TYPE,
        self::F_STATUS,
    ];

    protected $resolveAble = [
        self::FR_USER,
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(new EmployeeObserver());
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
