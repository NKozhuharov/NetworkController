<?php

namespace Database\Seeders;

use App\Http\Models\CommonArea;
use App\Http\Models\Customer;
use App\Http\Models\Room;
use App\Http\Models\RoomInventoryTemplate;
use App\Http\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class RoomSeeder extends BaseSeeder
{
    /** @var string[] A list example Common Area names */
    private $commonAreaSeeds = [
        'Floor',
        'Reception',
        'Public WC',
        'SPA',
        'Conference Room',
        'Breakfast Area',
    ];

    protected $lowCount = 60;
    protected $highCount = 80;

    /**
     * Requires a list of customers
     *
     * RoomsSeeder constructor.
     * @param array $customers
     */
    public function __construct(array $customers)
    {
        foreach ($customers as $customer) {
            $roomTypes = DB::table(RoomType::TABLE_NAME)
                ->where(RoomType::F_CUSTOMER_ID, $customer->{Customer::F_ID})
                ->get();
            $roomTemplates = DB::table(RoomInventoryTemplate::TABLE_NAME)
                ->where(RoomInventoryTemplate::F_CUSTOMER_ID, $customer->{Customer::F_ID})
                ->get();
            $usedCommonAreas = [];

            for ($i = 0; $i < $this->getCount(); $i++) {
                $roomTypeIndex = rand(0, count($roomTypes) - 1);
                $roomTemplateId = $roomTemplates[rand(0, count($roomTemplates) - 1)]->{BaseModel::F_ID};
                $roomName = RandomDataGenerator::getRandomNumericString(3);
                $floor = rand(-2, 5);
                if ($floor === 0) {
                    $floor = 'Ground floor';
                }

                if ($roomTypes[$roomTypeIndex]->{RoomType::F_NAME} === CommonArea::TYPE_COMMON_AREA) {
                    $roomTemplateId = NULL;
                    $roomName = $this->getCommonAreaName($usedCommonAreas, $floor);
                    if ($roomName === '') {
                        $roomName = RandomDataGenerator::getRandomNumericString(3);
                        $roomTypeIndex = rand(1, count($roomTypes) - 1);
                    }
                }

                $this->objects[] = [
                    Room::F_CUSTOMER_ID                => $customer->{Customer::F_ID},
                    Room::F_ROOM_TYPE_ID               => $roomTypes[$roomTypeIndex]->{BaseModel::F_ID},
                    Room::F_ROOM_INVENTORY_TEMPLATE_ID => $roomTemplateId,
                    Room::F_NAME                       => $roomName,
                    Room::F_FLOOR                      => $floor,
                ];
            }
        }
    }

    /**
     * Make sure Common area names are not duplicated for the same Customer
     *
     * @param $usedCommonAreas
     * @param $floor
     * @return mixed|string
     */
    private function getCommonAreaName(&$usedCommonAreas, $floor)
    {
        if (count($usedCommonAreas) === count($this->commonAreaSeeds)) {
            return '';
        }

        $roomName = RandomDataGenerator::getRandomElementFromArray($this->commonAreaSeeds);
        if ($roomName === 'Floor') {
            $roomName .= ' ' . $floor;
        }
        if (!in_array($roomName, $usedCommonAreas)) {
            $usedCommonAreas[] = $roomName;
            return $roomName;
        }

        return $this->getCommonAreaName($usedCommonAreas, $floor);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->objects as $object) {
            $this->insertedObjects[] = Room::create($object);
        }
    }
}
