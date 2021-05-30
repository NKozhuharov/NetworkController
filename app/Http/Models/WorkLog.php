<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\WorkLogTransformer;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkLog extends TypesOfCleaning
{
    const TABLE_NAME = 'work_log';

    const FR_EMPLOYEE = 'employee';
    const FR_ROOM = 'room';

    const F_EMPLOYEE_ID = self::FR_EMPLOYEE . '_' . self::F_ID;
    const F_ROOM_ID = self::FR_ROOM . '_' . self::F_ID;
    const F_TIME_START = 'time_start';
    const F_TIME_END = 'time_end';

    protected $table = self::TABLE_NAME;

    protected $transformer = WorkLogTransformer::class;

    protected $orderAble = [
        self::F_EMPLOYEE_ID,
        self::F_ROOM_ID,
        self::F_TYPE_OF_CLEANING,
        self::F_TIME_START,
        self::F_TIME_END,
    ];

    protected $fillable = [
        self::F_EMPLOYEE_ID,
        self::F_ROOM_ID,
        self::F_TYPE_OF_CLEANING,
        self::F_TIME_START,
        self::F_TIME_END,
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
        self::F_TIME_START,
        self::F_TIME_END,
    ];

    protected $queryAble = [
        self::FR_EMPLOYEE => self::QUERYABLE_RELATED,
        self::FR_ROOM     => self::QUERYABLE_RELATED,
    ];

    protected $resolveAble = [
        self::FR_EMPLOYEE,
        self::FR_ROOM,
    ];

    protected $aggregateAble = [
        'report',
    ];

    /**
     * @return HasOne
     */
    public function employee(): HasOne
    {
        return $this->HasOne(
            Employee::class,
            Employee::F_ID,
            WorkLog::F_EMPLOYEE_ID
        );
    }

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
}
