<?php

namespace Database\Seeders;

use App\Http\Models\RoomInventoryTemplateItems;
use App\Http\Models\RoomInventoryTemplate;
use App\Http\Models\RoomItem;
use Nevestul4o\NetworkController\Database\BaseSeeder;

class RoomInventoryTemplateItemsSeeder extends BaseSeeder
{
    protected $lowCount = 1;
    protected $highCount = 10;

    /**
     * Requires a list of customers
     *
     * CustomerContactsSeeder constructor.
     * @param array $templates
     * @param array $items
     */
    public function __construct(array $templates, array $items)
    {
        foreach ($templates as $template) {
            $itemIds = [];
            for ($i = 0; $i < $this->getCount(); $i++) {
                $itemIds[] = $items[rand(0, count($items) - 1)]->{RoomItem::F_ID};
            }

            $itemIds = array_unique($itemIds);

            foreach ($itemIds as $itemId) {
                $this->objects[] = [
                    RoomInventoryTemplateItems::F_ROOM_INVENTORY_TEMPLATE_ID => $template->{RoomInventoryTemplate::F_ID},
                    RoomInventoryTemplateItems::F_ROOM_ITEM_ID               => $itemId,
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
            $this->insertedObjects[] = RoomInventoryTemplateItems::create($object);
        }
    }
}
