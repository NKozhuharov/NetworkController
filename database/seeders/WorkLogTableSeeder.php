<?php

namespace Database\Seeders;

use App\Http\Models\Schedule;
use App\Http\Models\WorkLog;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class WorkLogTableSeeder extends BaseSeeder
{
    protected $lowCount = 1;
    protected $highCount = 5;

    public function __construct(array $schedule)
    {
        foreach ($schedule as $scheduleEntry) {
            $cleaned = rand(0, 100);
            if ($cleaned >= 75) {
                $timeStart = RandomDataGenerator::getRandomDateTime(
                    date('Y-m-d', (strtotime($scheduleEntry->{Schedule::F_DATE}) + 86400)),
                    $scheduleEntry->{Schedule::F_DATE}
                );
                $object = [
                    WorkLog::F_EMPLOYEE_ID      => $scheduleEntry->{Schedule::F_EMPLOYEE_ID},
                    WorkLog::F_ROOM_ID          => $scheduleEntry->{Schedule::F_ROOM_ID},
                    WorkLog::F_TYPE_OF_CLEANING => $scheduleEntry->{Schedule::F_TYPE_OF_CLEANING},
                    WorkLog::F_TIME_START       => $timeStart,
                ];
                if (rand(0, 1) === 0) {
                    $object[WorkLog::F_TIME_END] = RandomDataGenerator::getRandomDateTime(
                        date('Y-m-d', (strtotime($scheduleEntry->{Schedule::F_DATE}) + 86400)),
                        $timeStart
                    );
                } else {
                    $object[WorkLog::F_TIME_START] = substr($timeStart, 0, -8) . '12:00:00';
                }
                $this->objects[] = $object;
            }
        }
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->objects as $object) {
            $this->insertedObjects[] = WorkLog::create($object);
        }
    }
}
