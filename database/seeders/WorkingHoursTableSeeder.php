<?php

namespace Database\Seeders;

use App\Http\Models\WorkingHours;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class WorkingHoursTableSeeder extends BaseSeeder
{
    /**
     * Removes the randomly generated seconds at the end of the date time string and sets them to :00.
     *
     * @param string $time
     * @return string
     */
    private function removeSecondsFromTime(string $time): string
    {
        return substr($time, 0, -3) . ':00';
    }

    /**
     * Work day beginning - between 7:00 and 9:00
     * Work day break starts - between 12:00 and 13:00
     * Work day break ends - between 13:00 and 14:00
     * Work day end - between 17:00 and 19:00
     *
     * WorkingHoursTableSeeder constructor.
     * @param array $employees
     */
    public function __construct(array $employees)
    {
        foreach ($employees as $employee) {
            for ($i = -8; $i < -1; $i++) {
                $date = date('Y-m-d', time() + $i * 86400);
                $timeStart = RandomDataGenerator::getRandomDateTime(
                    $date . ' 09:00:00',
                    $date . ' 07:00:00'
                );
                $timeStart = $this->removeSecondsFromTime($timeStart);

                $timeBreakStart = RandomDataGenerator::getRandomDateTime(
                    $date . ' 13:00:00',
                    $date . ' 12:00:00'
                );
                $timeBreakStart = $this->removeSecondsFromTime($timeBreakStart);

                $timeBreakEnd = RandomDataGenerator::getRandomDateTime(
                    $date . ' 14:00:00',
                    $timeBreakStart
                );
                $timeBreakEnd = $this->removeSecondsFromTime($timeBreakEnd);

                $timeEnd = RandomDataGenerator::getRandomDateTime(
                    $date . ' 19:00:00',
                    $date . ' 17:00:00'
                );
                $timeEnd = $this->removeSecondsFromTime($timeEnd);

                $this->objects[] = [
                    WorkingHours::F_EMPLOYEE_ID    => $employee->{BaseModel::F_ID},
                    WorkingHours::F_TYPE           => WorkingHours::TYPE_WORK,
                    WorkingHours::F_INTERVAL_START => $timeStart,
                    WorkingHours::F_INTERVAL_END   => $timeBreakStart,
                ];
                $this->objects[] = [
                    WorkingHours::F_EMPLOYEE_ID    => $employee->{BaseModel::F_ID},
                    WorkingHours::F_TYPE           => WorkingHours::TYPE_BREAK,
                    WorkingHours::F_INTERVAL_START => $timeBreakStart,
                    WorkingHours::F_INTERVAL_END   => $timeBreakEnd,
                ];
                $this->objects[] = [
                    WorkingHours::F_EMPLOYEE_ID    => $employee->{BaseModel::F_ID},
                    WorkingHours::F_TYPE           => WorkingHours::TYPE_WORK,
                    WorkingHours::F_INTERVAL_START => $timeBreakEnd,
                    WorkingHours::F_INTERVAL_END   => $timeEnd,
                ];
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
            $this->insertedObjects[] = WorkingHours::create($object);
        }
    }
}
