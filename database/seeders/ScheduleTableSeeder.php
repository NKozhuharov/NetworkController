<?php

namespace Database\Seeders;

use App\Http\Models\CommonArea;
use App\Http\Models\Customer;
use App\Http\Models\Employee;
use App\Http\Models\Room;
use App\Http\Models\RoomType;
use App\Http\Models\Schedule;
use App\Http\Models\TypesOfCleaning;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class ScheduleTableSeeder extends BaseSeeder
{
    protected $lowCount = 10;
    protected $highCount = 15;

    /**
     * @var array
     */
    private $usedRoomsByDate = [];

    /**
     * Make sure that a rooms is used only once per day
     * Maximum of 200 attempts to select a random room
     *
     * @param array $customerRooms
     * @param string $date
     * @param int $attempts
     * @return Room|null
     */
    private function getRoom(array $customerRooms, string $date, int $attempts = 0): ?Room
    {
        if ($attempts === 200) {
            return NULL;
        }

        if (isset($this->usedRoomsByDate[$date]) && count($customerRooms) === count($this->usedRoomsByDate[$date])) {
            return NULL;
        }

        $room = RandomDataGenerator::getRandomElementFromArray($customerRooms);
        if (isset($this->usedRoomsByDate[$date]) && in_array($room->{Room::F_ID}, $this->usedRoomsByDate[$date])) {
            return $this->getRoom($customerRooms, $date, $attempts + 1);
        }

        $this->usedRoomsByDate[$date][] = $room->{Room::F_ID};
        return $room;
    }

    public function __construct(array $employees, array $customers, array $rooms)
    {
        foreach ($employees as $employee) {
            for ($i = 0; $i < 7; $i++) {
                $customer = RandomDataGenerator::getRandomElementFromArray($customers);
                $customerRooms = [];
                foreach ($rooms as $room) {
                    if ($room->{Room::F_CUSTOMER_ID} === $customer->{Customer::F_ID}) {
                        $customerRooms[] = $room;
                    }
                }
                $date = date('Y-m-d', time() + $i * 86400);

                for ($j = 0; $j < $this->getCount(); $j++) {
                    $room = $this->getRoom($customerRooms, $date);
                    if ($room === NULL) {
                        break;
                    }

                    $typeOfCleaning = TypesOfCleaning::TYPE_OF_CLEANING_CLEANING;
                    if ($room->{Room::FR_ROOM_TYPE}->{RoomType::F_NAME} != CommonArea::TYPE_COMMON_AREA) {
                        $typeOfCleaning = RandomDataGenerator::getRandomElementFromArray(
                            [
                                TypesOfCleaning::TYPE_OF_CLEANING_DEPARTURE,
                                TypesOfCleaning::TYPE_OF_CLEANING_STAY_OVER,
                            ]
                        );
                    }

                    $object = [
                        Schedule::F_EMPLOYEE_ID      => $employee->{Employee::F_ID},
                        Schedule::F_ROOM_ID          => $room->{Room::F_ID},
                        Schedule::F_TYPE_OF_CLEANING => $typeOfCleaning,
                        Schedule::F_DATE             => $date,
                    ];

                    $this->objects[] = $object;
                }
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
            $this->insertedObjects[] = Schedule::create($object);
        }
    }
}
