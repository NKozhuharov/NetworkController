<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\RoomType;
use League\Fractal\TransformerAbstract;

class RoomTypeTransformer extends TransformerAbstract
{
    /**
     * @param RoomType|null $roomType
     *
     * @return array
     */
    public function transform(RoomType $roomType = NULL): array
    {
        if ($roomType) {
            $response = [
                RoomType::F_ID          => (int)$roomType->{RoomType::F_ID},
                RoomType::F_CUSTOMER_ID => (int)$roomType->{RoomType::F_CUSTOMER_ID},
                RoomType::F_NAME        => (string)$roomType->{RoomType::F_NAME},
            ];
        }
        return $response ?? [];
    }
}
