<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\MinibarItemMovementTransformer;
use App\Observers\MinibarItemMovementObserver;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseModel;

class MinibarItemMovement extends BaseModel
{
    const TABLE_NAME = 'minibar_item_movements';

    const FR_MINIBAR_ITEM = 'minibar_item';
    const FR_USER = 'user';
    const FR_ROOM = 'room';

    const F_MINIBAR_ITEM_ID = self::FR_MINIBAR_ITEM . '_' . self::F_ID;
    const F_USER_ID = self::FR_USER . '_' . self::F_ID;
    const F_ROOM_ID = self::FR_ROOM . '_' . self::F_ID;

    const F_MOVEMENT = 'movement';

    protected $table = self::TABLE_NAME;

    protected $transformer = MinibarItemMovementTransformer::class;

    protected $orderAble = [
        self::F_ID,
        self::F_MINIBAR_ITEM_ID,
        self::F_USER_ID,
        self::F_ROOM_ID,
        self::F_CREATED_AT,
    ];

    protected $fillable = [
        self::F_MINIBAR_ITEM_ID,
        self::F_USER_ID,
        self::F_ROOM_ID,
        self::F_MOVEMENT,
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
        self::F_MINIBAR_ITEM_ID,
        self::F_USER_ID,
        self::F_ROOM_ID,
        self::F_CREATED_AT,
    ];

    protected $resolveAble = [
        self::FR_MINIBAR_ITEM,
        self::FR_USER,
        self::FR_ROOM,
    ];

    public static function boot()
    {
        parent::boot();
        self::observe(new MinibarItemMovementObserver());
    }

    /**
     * @return HasOne
     */
    public function room(): HasOne
    {
        return $this->HasOne(
            Room::class,
            BaseModel::F_ID,
            self::F_ROOM_ID
        );
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

    /**
     * @return HasOne
     */
    public function minibar_item(): HasOne
    {
        return $this->HasOne(
            MinibarItem::class,
            BaseModel::F_ID,
            self::F_MINIBAR_ITEM_ID
        );
    }
}
