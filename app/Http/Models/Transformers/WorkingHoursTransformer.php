<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\WorkingHours;
use League\Fractal\TransformerAbstract;

class WorkingHoursTransformer extends TransformerAbstract
{
    /**
     * @param WorkingHours|null $workingHours
     *
     * @return array
     */
    public function transform(WorkingHours $workingHours = NULL): array
    {
        if ($workingHours) {
            $response = [
                WorkingHours::F_ID             => (int)$workingHours->{WorkingHours::F_ID},
                WorkingHours::F_EMPLOYEE_ID    => (int)$workingHours->{WorkingHours::F_EMPLOYEE_ID},
                WorkingHours::F_TYPE           => (string)$workingHours->{WorkingHours::F_TYPE},
                WorkingHours::F_INTERVAL_START => (string)$workingHours->{WorkingHours::F_INTERVAL_START},
                WorkingHours::F_INTERVAL_END   => (string)$workingHours->{WorkingHours::F_INTERVAL_END},
            ];
        }
        return $response ?? [];
    }

    public function includeEmployee(WorkingHours $workingHours)
    {
        return $workingHours->employee()
            ? $this->item($workingHours->{WorkingHours::FR_EMPLOYEE}, new EmployeeTransformer())
            : $this->null();
    }
}
