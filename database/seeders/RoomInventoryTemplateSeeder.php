<?php

namespace Database\Seeders;

use App\Http\Models\Customer;
use App\Http\Models\RoomInventoryTemplate;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class RoomInventoryTemplateSeeder extends BaseSeeder
{
    protected $lowCount = 1;
    protected $highCount = 5;

    /**
     * Requires a list of customers
     *
     * CustomerContactsSeeder constructor.
     * @param array $customers
     */
    public function __construct(array $customers)
    {
        foreach ($customers as $customer) {
            for ($i = 0; $i < $this->getCount(); $i++) {
                $this->objects[] = [
                    RoomInventoryTemplate::F_CUSTOMER_ID => $customer->{Customer::F_ID},
                    RoomInventoryTemplate::F_NAME        => RandomDataGenerator::getRandomString(RoomInventoryTemplate::TABLE_NAME),
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
            $this->insertedObjects[] = RoomInventoryTemplate::create($object);
        }
    }
}
