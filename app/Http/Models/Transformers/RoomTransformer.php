<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\Room;
use League\Fractal\TransformerAbstract;

class RoomTransformer extends TransformerAbstract
{
    /**
     * @param Room|null $room
     *
     * @return array
     */
    public function transform(Room $room = NULL): array
    {
        if ($room) {
            $response = [
                Room::F_ID                         => (int)$room->{Room::F_ID},
                Room::F_CUSTOMER_ID                => (int)$room->{Room::F_CUSTOMER_ID},
                Room::F_ROOM_TYPE_ID               => (int)$room->{Room::F_ROOM_TYPE_ID},
                Room::F_ROOM_INVENTORY_TEMPLATE_ID => !empty($room->{Room::F_ROOM_INVENTORY_TEMPLATE_ID}) ? (int)$room->{Room::F_ROOM_INVENTORY_TEMPLATE_ID} : NULL,
                Room::F_NAME                       => (string)$room->{Room::F_NAME},
                Room::F_FLOOR                      => (string)$room->{Room::F_FLOOR},
            ];
        }
        return $response ?? [];
    }

    public function includeRoomType(Room $room)
    {
        return $room->room_type()
            ? $this->item($room->{Room::FR_ROOM_TYPE}, new RoomTypeTransformer())
            : $this->null();
    }

    public function includeRoomInventoryTemplate(Room $room)
    {
        return $room->room_inventory_template()
            ? $this->item($room->{Room::FR_ROOM_INVENTORY_TEMPLATE}, new RoomInventoryTemplateTransformer())
            : $this->null();
    }
}
