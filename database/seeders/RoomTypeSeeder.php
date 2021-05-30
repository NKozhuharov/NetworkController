<?php

namespace Database\Seeders;

use App\Http\Models\Customer;
use App\Http\Models\RoomType;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class RoomTypeSeeder extends BaseSeeder
{
    protected $lowCount = 2;
    protected $highCount = 6;

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
                    RoomType::F_CUSTOMER_ID => $customer->{Customer::F_ID},
                    RoomType::F_NAME        => RandomDataGenerator::getRandomString(RoomType::TABLE_NAME),
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
            $this->insertedObjects[] = RoomType::create($object);
        }
    }
}
