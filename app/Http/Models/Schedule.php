<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\ScheduleTransformer;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Schedule extends TypesOfCleaning
{
    const TABLE_NAME = 'schedule';

    const FR_EMPLOYEE = 'employee';
    const FR_ROOM = 'room';

    const F_EMPLOYEE_ID = self::FR_EMPLOYEE . '_' . self::F_ID;
    const F_ROOM_ID = self::FR_ROOM . '_' . self::F_ID;

    const F_DATE = 'date';

    protected $table = self::TABLE_NAME;

    protected $transformer = ScheduleTransformer::class;

    protected $orderAble = [
        self::F_EMPLOYEE_ID,
        self::F_ROOM_ID,
        self::F_TYPE_OF_CLEANING,
        self::F_DATE,
    ];

    protected $fillable = [
        self::F_EMPLOYEE_ID,
        self::F_ROOM_ID,
        self::F_TYPE_OF_CLEANING,
        self::F_DATE,
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
        self::F_EMPLOYEE_ID,
        self::F_ROOM_ID,
        self::F_TYPE_OF_CLEANING,
        self::F_DATE,
    ];

    protected $resolveAble = [
        self::FR_ROOM,
        self::FR_ROOM . '-' . Room::FR_ROOM_TYPE,
    ];

    /**
     * @return HasOne
     */
    public function room(): HasOne
    {
        return $this->HasOne(
            Room::class,
            Room::F_ID,
            self::F_ROOM_ID
        );
    }

    /**
     * @return HasOneThrough
     */
    public function room_room_type(): HasOneThrough
    {
        return $this->HasOneThrough(
            RoomType::class,
            Room::class,
            Schedule::F_ROOM_ID,
            BaseModel::F_ID,
            BaseModel::F_ID,
            Room::F_ROOM_TYPE_ID
        );
    }
}
