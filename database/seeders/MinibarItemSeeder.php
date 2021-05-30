<?php

namespace Database\Seeders;

use Faker;
use App\Http\Models\Customer;
use App\Http\Models\MinibarItem;
use Nevestul4o\NetworkController\Database\BaseSeeder;

class MinibarItemSeeder extends BaseSeeder
{
    protected $lowCount = 5;
    protected $highCount = 10;

    /**
     * Requires a list of customers
     *
     * CustomerContactsSeeder constructor.
     * @param array $customers
     */
    public function __construct(array $customers)
    {

        foreach ($customers as $customer) {
            $usedNames = [];
            for ($i = 0; $i < $this->getCount(); $i++) {
                $this->objects[] = [
                    MinibarItem::F_CUSTOMER_ID => $customer->{Customer::F_ID},
                    MinibarItem::F_NAME        => $this->getBeverageName($usedNames),
                    MinibarItem::F_PICTURE     => NULL,
                ];
            }
        }
    }

    private function getBeverageName(&$usedNames)
    {
        $faker = Faker\Factory::create();
        $faker->addProvider(new \FakerRestaurant\Provider\de_DE\Restaurant($faker));
        $beverageName = $faker->beverageName();
        if (!in_array($beverageName, $usedNames)) {
            $usedNames[] = $beverageName;
            return $beverageName;
        }

        return $this->getBeverageName($usedNames);
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->objects as $object) {
            $this->insertedObjects[] = MinibarItem::create($object);
        }
    }
}
