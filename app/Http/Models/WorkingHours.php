<?php

namespace App\Http\Models;

use App\Http\Models\Transformers\WorkingHoursTransformer;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Nevestul4o\NetworkController\Models\BaseModel;

class WorkingHours extends BaseModel
{
    const TABLE_NAME = 'working_hours';

    const FR_EMPLOYEE = 'employee';

    const F_EMPLOYEE_ID = self::FR_EMPLOYEE . '_' . self::F_ID;
    const F_TYPE = 'type';
    const F_INTERVAL_START = 'interval_start';
    const F_INTERVAL_END = 'interval_end';

    const TYPE_WORK = 'W';
    const TYPE_BREAK = 'B';

    const TYPES = [
        'Работа'  => self::TYPE_WORK,
        'Почивка' => self::TYPE_BREAK,
    ];

    protected $table = self::TABLE_NAME;

    protected $transformer = WorkingHoursTransformer::class;

    protected $fillable = [
        self::F_EMPLOYEE_ID,
        self::F_TYPE,
        self::F_INTERVAL_START,
        self::F_INTERVAL_END,
    ];

    protected $orderAble = [
        self::F_EMPLOYEE_ID,
        self::F_TYPE,
        self::F_INTERVAL_START,
        self::F_INTERVAL_END,
    ];

    protected $dates = [
        self::F_CREATED_AT,
        self::F_UPDATED_AT,
        self::F_DELETED_AT,
    ];

    protected $filterAble = [
        self::F_EMPLOYEE_ID,
        self::F_TYPE,
        self::F_INTERVAL_START,
        self::F_INTERVAL_END,
    ];

    protected $queryAble = [
        self::FR_EMPLOYEE => self::QUERYABLE_RELATED,
    ];

    protected $resolveAble = [
        self::FR_EMPLOYEE,
    ];

    protected $aggregateAble = [
        'report'
    ];

    /**
     * @return HasOne
     */
    public function employee(): HasOne
    {
        return $this->HasOne(
            Employee::class,
            Employee::F_ID,
            WorkingHours::F_EMPLOYEE_ID
        );
    }
}
