<?php

namespace Database\Seeders;

use Faker;
use App\Http\Models\Customer;
use App\Http\Models\CustomerContact;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class CustomerContactSeeder extends BaseSeeder
{
    protected $lowCount = 0;
    protected $highCount = 5;

    /**
     * Requires a list of customers
     *
     * CustomerContactsSeeder constructor.
     * @param array $customers
     */
    public function __construct(array $customers)
    {
        $faker = Faker\Factory::create();
        $faker->addProvider(new Faker\Provider\de_DE\Address($faker));
        $faker->addProvider(new Faker\Provider\de_DE\Person($faker));
        $faker->addProvider(new Faker\Provider\de_DE\PhoneNumber($faker));

        foreach ($customers as $customer) {
            for ($i = 0; $i < $this->getCount(); $i++) {
                $this->objects[] = [
                    CustomerContact::F_CUSTOMER_ID => $customer->{Customer::F_ID},
                    CustomerContact::F_TITLE       => RandomDataGenerator::getRandomElementFromArray(CustomerContact::TITLES),
                    CustomerContact::F_NAME        => $faker->name,
                    CustomerContact::F_PHONE       => $faker->phoneNumber,
                    CustomerContact::F_EMAIL       => $faker->email,
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
            $this->insertedObjects[] = CustomerContact::create($object);
        }
    }
}
