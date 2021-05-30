<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseUser;
use Nevestul4o\NetworkController\Models\BaseModel;

class User extends BaseUser
{
    const TABLE_NAME = 'users';

    const FR_EMPLOYEE = 'employee';
    const FR_CUSTOMER = 'customer';

    const F_NAME = 'name';
    const F_EMAIL = 'email';
    const F_EMAIL_VERIFIED_AT = 'email_verified_at';
    const F_PASSWORD = 'password';
    const F_TYPE = 'type';

    const DEFAULT_PASSWORD = 'password';

    const TYPE_ADMIN = 'A';
    const TYPE_EMPLOYEE = 'E';
    const TYPE_CUSTOMER = 'C';

    const TYPES = [
        'Администратор' => self::TYPE_ADMIN,
        'Служител'      => self::TYPE_EMPLOYEE,
        'Клиент'        => self::TYPE_CUSTOMER,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::F_NAME,
        self::F_EMAIL,
        self::F_PASSWORD,
    ];
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = self::TABLE_NAME;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        self::F_EMAIL_VERIFIED_AT => 'datetime',
    ];

    public function employee(): HasOne
    {
        return $this->HasOne(
            Employee::class,
            Employee::F_USER_ID,
            BaseModel::F_ID
        );
    }

    public function customer(): HasOne
    {
        return $this->HasOne(
            Customer::class,
            Customer::F_USER_ID,
            BaseModel::F_ID
        );
    }

    /**
     * Checks if the user is Admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->{self::F_TYPE} === self::TYPE_ADMIN;
    }

    /**
     * Checks if the user is Customer
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->{self::F_TYPE} === self::TYPE_CUSTOMER;
    }

    /**
     * Checks if the user is Employee
     * @return bool
     */
    public function isEmployee(): bool
    {
        return $this->{self::F_TYPE} === self::TYPE_EMPLOYEE;
    }
}
