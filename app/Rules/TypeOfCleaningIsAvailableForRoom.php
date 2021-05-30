<?php

namespace App\Rules;

use App\Http\Models\CommonArea;
use App\Http\Models\Room;
use App\Http\Models\RoomType;
use App\Http\Models\TypesOfCleaning;
use Illuminate\Contracts\Validation\Rule;

class TypeOfCleaningIsAvailableForRoom implements Rule
{
    /** @var RoomType */
    private $roomType;

    public function __construct(int $roomId = NULL)
    {
        if ($roomId !== NULL) {
            $this->roomType = Room::findOrFail($roomId)->{Room::FR_ROOM_TYPE};
        }
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        //ignore cases when the room id is not provided
        if (empty($this->roomType)) {
            return TRUE;
        }

        if (
            $this->roomType->{RoomType::F_NAME} === CommonArea::TYPE_COMMON_AREA
            && $value !== TypesOfCleaning::TYPE_OF_CLEANING_CLEANING
        ) {
            return FALSE;
        }

        if (
            $this->roomType->{RoomType::F_NAME} !== CommonArea::TYPE_COMMON_AREA
            && $value === TypesOfCleaning::TYPE_OF_CLEANING_CLEANING
        ) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return "This type of cleaning is not available for the selected room";
    }
}
