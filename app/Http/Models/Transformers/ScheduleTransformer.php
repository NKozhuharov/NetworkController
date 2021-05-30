<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\Room;
use App\Http\Models\Schedule;
use League\Fractal\TransformerAbstract;

class ScheduleTransformer extends TransformerAbstract
{
    /**
     * @param Schedule|null $schedule
     *
     * @return array
     */
    public function transform(Schedule $schedule = NULL): array
    {
        if ($schedule) {
            $response = [
                Schedule::F_ID               => (int)$schedule->{Schedule::F_ID},
                Schedule::F_EMPLOYEE_ID      => (int)$schedule->{Schedule::F_EMPLOYEE_ID},
                Schedule::F_ROOM_ID          => (int)$schedule->{Schedule::F_ROOM_ID},
                Schedule::F_TYPE_OF_CLEANING => (string)$schedule->{Schedule::F_TYPE_OF_CLEANING},
                Schedule::F_DATE             => (string)$schedule->{Schedule::F_DATE},
            ];
        }
        return $response ?? [];
    }

    public function includeRoom(Schedule $schedule)
    {
        return $schedule->room()
            ? $this->item($schedule->{Schedule::FR_ROOM}, new RoomTransformer())
            : $this->null();
    }

    public function includeRoomRoomType(Schedule $schedule)
    {
        return $schedule->room_room_type()
            ? $this->item($schedule->{Schedule::FR_ROOM}->{Room::FR_ROOM_TYPE}, new RoomTypeTransformer())
            : $this->null();
    }
}
