<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\WorkLog;
use League\Fractal\TransformerAbstract;

class WorkLogTransformer extends TransformerAbstract
{
    /**
     * @param WorkLog|null $workLog
     *
     * @return array
     */
    public function transform(WorkLog $workLog = NULL): array
    {
        if ($workLog) {
            $response = [
                WorkLog::F_ID               => (int)$workLog->{WorkLog::F_ID},
                WorkLog::F_EMPLOYEE_ID      => (int)$workLog->{WorkLog::F_EMPLOYEE_ID},
                WorkLog::F_ROOM_ID          => (int)$workLog->{WorkLog::F_ROOM_ID},
                WorkLog::F_TYPE_OF_CLEANING => (string)$workLog->{WorkLog::F_TYPE_OF_CLEANING},
                WorkLog::F_TIME_START       => (string)$workLog->{WorkLog::F_TIME_START},
                WorkLog::F_TIME_END         => (string)$workLog->{WorkLog::F_TIME_END},
            ];
        }
        return $response ?? [];
    }

    public function includeEmployee(WorkLog $workLog)
    {
        return $workLog->employee()
            ? $this->item($workLog->{WorkLog::FR_EMPLOYEE}, new EmployeeTransformer())
            : $this->null();
    }

    public function includeRoom(WorkLog $workLog)
    {
        return $workLog->room()
            ? $this->item($workLog->{WorkLog::FR_ROOM}, new RoomTransformer())
            : $this->null();
    }
}
