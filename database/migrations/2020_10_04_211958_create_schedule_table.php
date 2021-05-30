<?php

use App\Http\Models\Employee;
use App\Http\Models\Room;
use App\Http\Models\Schedule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Schedule::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(Schedule::F_EMPLOYEE_ID);
            $table->unsignedBigInteger(Schedule::F_ROOM_ID);
            $table->foreign(Schedule::F_EMPLOYEE_ID)
                ->references(Employee::F_ID)
                ->on(Employee::TABLE_NAME)
                ->onUpdate('cascade');
            $table->foreign(Schedule::F_ROOM_ID)
                ->references(Room::F_ID)
                ->on(Room::TABLE_NAME)
                ->onUpdate('cascade');
            $table->char(Schedule::F_TYPE_OF_CLEANING, 1);
            $table->date(Schedule::F_DATE);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Schedule::TABLE_NAME);
    }
}
