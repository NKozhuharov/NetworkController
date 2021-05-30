<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\RoomItem;
use League\Fractal\TransformerAbstract;

class RoomItemTransformer extends TransformerAbstract
{
    /**
     * @param RoomItem|null $roomItem
     *
     * @return array
     */
    public function transform(RoomItem $roomItem = NULL): array
    {
        if ($roomItem) {
            $response = [
                RoomItem::F_ID   => (int)$roomItem->{RoomItem::F_ID},
                RoomItem::F_NAME => (string)$roomItem->{RoomItem::F_NAME},
            ];
        }
        return $response ?? [];
    }
}
