<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        printf("Seeding Users" . PHP_EOL);
        $userSeeder = new UserTableSeeder();
        $userSeeder->run();

        printf("Seeding Countries" . PHP_EOL);
        $countrySeeder = new CountriesTableSeeder();
        $countrySeeder->run();

        printf("Seeding Employees" . PHP_EOL);
        $employeeSeeder = new EmployeeTableSeeder($countrySeeder->getInsertedObjects());
        $employeeSeeder->run();

        printf("Seeding RoomItems" . PHP_EOL);
        $roomItemSeeder = new RoomItemTableSeeder();
        $roomItemSeeder->run();

        printf("Seeding Customers" . PHP_EOL);
        $customerSeeder = new CustomerTableSeeder($countrySeeder->getInsertedObjects());
        $customerSeeder->run();

        printf("Seeding CustomerContacts" . PHP_EOL);
        $customerContactSeeder = new CustomerContactSeeder($customerSeeder->getInsertedObjects());
        $customerContactSeeder->run();

        printf("Seeding MinibarItems" . PHP_EOL);
        $minibarItemsSeeder = new MinibarItemSeeder($customerSeeder->getInsertedObjects());
        $minibarItemsSeeder->run();

        printf("Seeding RoomTypes" . PHP_EOL);
        $roomTypeSeeder = new RoomTypeSeeder($customerSeeder->getInsertedObjects());
        $roomTypeSeeder->run();

        printf("Seeding RoomInventoryTemplates" . PHP_EOL);
        $roomInventoryTemplateSeeder = new RoomInventoryTemplateSeeder($customerSeeder->getInsertedObjects());
        $roomInventoryTemplateSeeder->run();

        printf("Seeding RoomInventoryTemplatesItems" . PHP_EOL);
        $roomInventoryTemplateItemsSeeder = new RoomInventoryTemplateItemsSeeder(
            $roomInventoryTemplateSeeder->getInsertedObjects(),
            $roomItemSeeder->getInsertedObjects()
        );
        $roomInventoryTemplateItemsSeeder->run();

        printf("Seeding Rooms" . PHP_EOL);
        $roomSeeder = new RoomSeeder(
            $customerSeeder->getInsertedObjects()
        );
        $roomSeeder->run();

        printf("Seeding Schedule" . PHP_EOL);
        $scheduleSeeder = new ScheduleTableSeeder(
            $employeeSeeder->getInsertedObjects(),
            $customerSeeder->getInsertedObjects(),
            $roomSeeder->getInsertedObjects()
        );
        $scheduleSeeder->run();

        printf("Seeding WorkLog" . PHP_EOL);
        $workLogSeeder = new WorkLogTableSeeder(
            $scheduleSeeder->getInsertedObjects()
        );
        $workLogSeeder->run();

        print_r("Seeding MinibarItemMovement" . PHP_EOL);
        $minibarItemMovementsSeeder = new MinibarItemMovementSeeder(
            $minibarItemsSeeder->getInsertedObjects(),
            $userSeeder->getInsertedObjects(),
            $roomSeeder->getInsertedObjects()
        );
        $minibarItemMovementsSeeder->run();

        printf("Seeding Working Hours" . PHP_EOL);
        $workingHoursSeeder = new WorkingHoursTableSeeder($employeeSeeder->getInsertedObjects());
        $workingHoursSeeder->run();
    }
}
