<?php

namespace Nevestul4o\NetworkController\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseUser extends Authenticatable implements MustVerifyEmail
{
    use MustVerifyEmailTrait;
    use SoftDeletes;

    const TABLE_NAME = 'users';

    const F_NAME = 'name';
    const F_EMAIL = 'email';
    const F_EMAIL_VERIFIED_AT = 'email_verified_at';
    const F_PASSWORD = 'password';

    const DEFAULT_PASSWORD = 'password';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::F_NAME,
        self::F_EMAIL,
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
}
