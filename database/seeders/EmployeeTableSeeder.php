<?php

namespace Database\Seeders;

use App\Http\Models\Employee;
use Faker;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class EmployeeTableSeeder extends BaseSeeder
{
    protected $lowCount = 20;
    protected $highCount = 20;

    public function __construct(array $countries)
    {
        $faker = Faker\Factory::create();
        $faker->addProvider(new Faker\Provider\bg_BG\Person($faker));
        $faker->addProvider(new Faker\Provider\bg_BG\Internet($faker));
        $faker->addProvider(new Faker\Provider\bg_BG\PhoneNumber($faker));

        for ($i = 0; $i < $this->getCount(); $i++) {
            $idIssuedOnDate = RandomDataGenerator::getRandomDate();

            $this->objects[] = [
                Employee::F_NAME          => $faker->firstName,
                Employee::F_SECOND_NAME   => $faker->lastName,
                Employee::F_SURNAME       => $faker->lastName,
                Employee::F_EGN           => RandomDataGenerator::getRandomNumericString(10),
                Employee::F_ID_NUMBER     => RandomDataGenerator::getRandomNumericString(9),
                Employee::F_GENDER        => RandomDataGenerator::getRandomElementFromArray(Employee::GENDERS),
                Employee::F_DATE_OF_BIRTH => RandomDataGenerator::getRandomDate('2001-08-20'),
                Employee::F_COUNTRY_ID    => RandomDataGenerator::getRandomElementFromArray($countries)->{BaseModel::F_ID},
                Employee::F_ADDRESS       => $faker->streetAddress,
                Employee::F_ID_ISSUED_ON  => $idIssuedOnDate,
                Employee::F_ID_EXPIRES_ON => RandomDataGenerator::getRandomDate('', $idIssuedOnDate),
                Employee::F_PHONE         => $faker->phoneNumber,
                Employee::F_EMAIL         => $faker->email,
                Employee::F_TYPE          => RandomDataGenerator::getRandomElementFromArray(Employee::TYPES),
                Employee::F_STATUS        => RandomDataGenerator::getRandomElementFromArray(Employee::STATUSES),
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
            $this->insertedObjects[] = Employee::create($object);
        }
    }
}
