<?php

namespace Database\Seeders;

use App\Http\Models\RoomItem;
use Nevestul4o\NetworkController\Database\BaseSeeder;

class RoomItemTableSeeder extends BaseSeeder
{
    private $list = [
        'Bed',
        'Baby cot',
        'Desk',
        'Desk chair',
        'TV stand',
        'Dresser',
        'Nightstand',
        'Table',
        'Lamp',
        'Lounge chair',
        'Coffee machine',
        'Wardrobes',
        'Phone',
        'Air conditioner',
        'Sofa',
        'Arm chair',
        'Kettle',
        'Picture',
        'Hanger',
        'Rug',
        'Mirror',
    ];

    public function __construct()
    {
        foreach ($this->list as $item) {
            $this->objects[] = [
                RoomItem::F_NAME => $item,
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
            $this->insertedObjects[] = RoomItem::create($object);
        }
    }
}
