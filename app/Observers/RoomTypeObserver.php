<?php

namespace App\Observers;

use App\Http\Models\CommonArea;
use App\Http\Models\Room;
use App\Http\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoomTypeObserver
{
    /**
     * Listen to the RoomType updating event.
     * Checks if the room type is Common Area
     *
     * @param RoomType $roomType
     * @throws ValidationException
     */
    public function updating(RoomType $roomType)
    {
        if (
            $roomType->getOriginal(RoomType::F_NAME) === CommonArea::TYPE_COMMON_AREA
            && $roomType->{RoomType::F_NAME} !== CommonArea::TYPE_COMMON_AREA
        ) {
            throw ValidationException::withMessages(["Cannot update Common areas"]);
        }
    }

    /**
     * Listen to the RoomType deleting event.
     * Checks if the room type is Common Area
     * Checks if the room type has been used in a room
     *
     * @param RoomType $roomType
     * @throws ValidationException
     */
    public function deleting(RoomType $roomType)
    {
        if (
            $roomType->{RoomType::F_NAME} === CommonArea::TYPE_COMMON_AREA
        ) {
            throw ValidationException::withMessages(["Cannot delete this room type, it's a Common area"]);
        }

        $usedInTemplatesCount = DB::table(Room::TABLE_NAME)
            ->where(Room::F_ROOM_TYPE_ID, $roomType->{RoomType::F_ID})
            ->count();

        if ($usedInTemplatesCount) {
            throw ValidationException::withMessages(["Cannot delete this room type, it's been used in a room!"]);
        }
    }
}
