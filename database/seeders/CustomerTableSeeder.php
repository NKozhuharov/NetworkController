<?php

namespace Database\Seeders;

use App\Http\Models\Customer;
use Faker;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class CustomerTableSeeder extends BaseSeeder
{
    protected $lowCount = 5;
    protected $highCount = 5;

    public function __construct(array $countries)
    {
        $faker = Faker\Factory::create();
        $faker->addProvider(new Faker\Provider\de_DE\Address($faker));
        $faker->addProvider(new Faker\Provider\de_DE\Person($faker));

        for ($i = 0; $i < $this->getCount(); $i++) {
            $this->objects[] = [
                Customer::F_NAME       => $faker->company,
                Customer::F_EMAIL      => $faker->email,
                Customer::F_COUNTRY_ID => RandomDataGenerator::getRandomElementFromArray($countries)->{BaseModel::F_ID},
                Customer::F_TOWN       => $faker->city,
                Customer::F_ADDRESS    => $faker->streetAddress,
            ];
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
            $this->insertedObjects[] = Customer::create($object);
        }
    }
}
