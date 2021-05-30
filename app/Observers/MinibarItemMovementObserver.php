<?php

namespace App\Observers;

use App\Http\Models\MinibarItem;
use App\Http\Models\MinibarItemMovement;

class MinibarItemMovementObserver
{
    /**
     * Handle the MinibarItemMovement "created" event.
     *
     * @param MinibarItemMovement $movement
     * @return void
     */
    public function created(MinibarItemMovement $movement)
    {
        $minibarItem = $movement->{MinibarItemMovement::FR_MINIBAR_ITEM};
        $minibarItem->{MinibarItem::F_AMOUNT} = $minibarItem->{MinibarItem::F_AMOUNT} + $movement->{MinibarItemMovement::F_MOVEMENT};
        $minibarItem->save();
    }

    /**
     * Handle the MinibarItemMovement "updated" event.
     *
     * @param MinibarItemMovement $movement
     * @return void
     */
    public function updated(MinibarItemMovement $movement)
    {
        $minibarItemOriginal = MinibarItem::find($movement->getOriginal(MinibarItemMovement::F_MINIBAR_ITEM_ID));
        $minibarItemOriginal->{MinibarItem::F_AMOUNT} = $minibarItemOriginal->{MinibarItem::F_AMOUNT} - $movement->getOriginal(MinibarItemMovement::F_MOVEMENT);
        $minibarItemOriginal->save();

        $minibarItem = $movement->{MinibarItemMovement::FR_MINIBAR_ITEM};
        $minibarItem->{MinibarItem::F_AMOUNT} = $minibarItem->{MinibarItem::F_AMOUNT} + $movement->{MinibarItemMovement::F_MOVEMENT};
        $minibarItem->save();
    }

    /**
     * Handle the MinibarItemMovement "deleted" event.
     *
     * @param MinibarItemMovement $movement
     * @return void
     */
    public function deleted(MinibarItemMovement $movement)
    {
        $minibarItem = $movement->{MinibarItemMovement::FR_MINIBAR_ITEM};
        $minibarItem->{MinibarItem::F_AMOUNT} = $minibarItem->{MinibarItem::F_AMOUNT} - $movement->{MinibarItemMovement::F_MOVEMENT};
        $minibarItem->save();
    }
}
