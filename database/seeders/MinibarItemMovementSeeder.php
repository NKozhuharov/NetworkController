<?php

namespace Database\Seeders;

use App\Http\Models\MinibarItemMovement;
use App\Http\Models\User;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Database\BaseSeeder;
use Nevestul4o\NetworkController\Database\RandomDataGenerator;

class MinibarItemMovementSeeder extends BaseSeeder
{
    protected $lowCount = 5;
    protected $highCount = 10;

    /**
     * Requires a list of minibar items, admins and rooms
     *
     * MinibarItemMovementSeeder constructor.
     * @param array $minibarItems
     * @param array $admins
     * @param array $rooms
     */
    public function __construct(array $minibarItems, array $admins, array $rooms)
    {
        $employees = User::where(User::F_TYPE, User::TYPE_EMPLOYEE)->get()->all();

        foreach ($minibarItems as $item) {
            for ($i = 0; $i < $this->getCount(); $i++) {
                $this->objects[] = [
                    MinibarItemMovement::F_MINIBAR_ITEM_ID => $item->{BaseModel::F_ID},
                    MinibarItemMovement::F_USER_ID         => RandomDataGenerator::getRandomElementFromArray($i === 0 ? $admins : $employees)->{BaseModel::F_ID},
                    MinibarItemMovement::F_ROOM_ID         => $i === 0 ? NULL : RandomDataGenerator::getRandomElementFromArray($rooms)->{BaseModel::F_ID},
                    MinibarItemMovement::F_MOVEMENT        => $i === 0 ? rand(0, 50) : rand(-2, -1),
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
            $this->insertedObjects[] = MinibarItemMovement::create($object);
        }
    }
}
