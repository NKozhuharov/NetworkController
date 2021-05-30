<?php

namespace App\Rules;

use App\Http\Models\WorkingHours;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class WorkingIntervalDoesNotOverlap implements Rule
{
    /**
     * The employee id, to which the WorkingInterval belongs (or will belong) to
     * @var int|null
     */
    private $employeeId;

    /**
     * If the request is update, set the existing WorkingInterval id here
     * @var int|null
     */
    private $workingIntervalId;

    /**
     * WorkingIntervalDoesNotOverlap constructor.
     * @param int|null $employeeId
     * @param int|null $workingIntervalId
     */
    public function __construct(int $employeeId = NULL, int $workingIntervalId = NULL)
    {
        $this->employeeId = $employeeId;
        $this->workingIntervalId = $workingIntervalId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        //ignore cases when the employee id is not provided
        if (empty($this->employeeId)) {
            return TRUE;
        }

        $intervalOverlaps = DB::table(WorkingHours::TABLE_NAME)
            ->where(WorkingHours::F_EMPLOYEE_ID, $this->employeeId)
            ->where(WorkingHours::F_INTERVAL_START, '<=', $value)
            ->where(WorkingHours::F_INTERVAL_END, '>', $value)
            ->whereNull(WorkingHours::F_DELETED_AT);

        //ignore the current WorkingInterval when updating
        if (!empty($this->workingIntervalId)) {
            $intervalOverlaps->where(WorkingHours::F_ID, '!=', $this->workingIntervalId);
        }

        $intervalOverlaps->get();

        if ($intervalOverlaps->count() > 0) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "Interval overlaps with another interval for this employee";
    }
}
