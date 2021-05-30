<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\MinibarItemMovement;
use League\Fractal\TransformerAbstract;

class MinibarItemMovementTransformer extends TransformerAbstract
{
    /**
     * @param MinibarItemMovement|null $minibarItemMovement
     * @return array
     */
    public function transform(MinibarItemMovement $minibarItemMovement = NULL): array
    {
        if ($minibarItemMovement) {
            $response = [
                MinibarItemMovement::F_ID              => (int)$minibarItemMovement->{MinibarItemMovement::F_ID},
                MinibarItemMovement::F_MINIBAR_ITEM_ID => (int)$minibarItemMovement->{MinibarItemMovement::F_MINIBAR_ITEM_ID},
                MinibarItemMovement::F_USER_ID         => (int)$minibarItemMovement->{MinibarItemMovement::F_USER_ID},
                MinibarItemMovement::F_ROOM_ID         => (int)$minibarItemMovement->{MinibarItemMovement::F_ROOM_ID},
                MinibarItemMovement::F_MOVEMENT        => (int)$minibarItemMovement->{MinibarItemMovement::F_MOVEMENT},
            ];
        }
        return $response ?? [];
    }

    public function includeRoom(MinibarItemMovement $movement)
    {
        return $movement->room()
            ? $this->item($movement->{MinibarItemMovement::FR_ROOM}, new RoomTransformer())
            : $this->null();
    }

    public function includeUser(MinibarItemMovement $movement)
    {
        return $movement->user()
            ? $this->item($movement->{MinibarItemMovement::FR_USER}, new UserTransformer())
            : $this->null();
    }

    public function includeMinibarItem(MinibarItemMovement $movement)
    {
        return $movement->minibar_item()
            ? $this->item($movement->{MinibarItemMovement::FR_MINIBAR_ITEM}, new MinibarItemTransformer())
            : $this->null();
    }
}
