<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\RoomInventoryTemplate;
use League\Fractal\TransformerAbstract;

class RoomInventoryTemplateTransformer extends TransformerAbstract
{
    /**
     * @param RoomInventoryTemplate|null $roomInventoryTemplate
     *
     * @return array
     */
    public function transform(RoomInventoryTemplate $roomInventoryTemplate = NULL): array
    {
        if ($roomInventoryTemplate) {
            $this->setDefaultIncludes($roomInventoryTemplate->getWithRelation());

            $response = [
                RoomInventoryTemplate::F_ID          => (int)$roomInventoryTemplate->{RoomInventoryTemplate::F_ID},
                RoomInventoryTemplate::F_CUSTOMER_ID => (int)$roomInventoryTemplate->{RoomInventoryTemplate::F_CUSTOMER_ID},
                RoomInventoryTemplate::F_NAME        => (string)$roomInventoryTemplate->{RoomInventoryTemplate::F_NAME},
            ];
        }
        return $response ?? [];
    }

    public function includeRoomItems(RoomInventoryTemplate $roomInventoryTemplate)
    {
        return $roomInventoryTemplate->room_items()
            ? $this->collection($roomInventoryTemplate->{RoomInventoryTemplate::F_ROOM_ITEMS}, new RoomItemTransformer())
            : $this->null();
    }
}
