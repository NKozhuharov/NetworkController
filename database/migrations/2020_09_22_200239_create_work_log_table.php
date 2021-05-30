<?php

use App\Http\Models\Employee;
use App\Http\Models\Room;
use App\Http\Models\WorkLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(WorkLog::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(WorkLog::F_EMPLOYEE_ID);
            $table->unsignedBigInteger(WorkLog::F_ROOM_ID);
            $table->foreign(WorkLog::F_EMPLOYEE_ID)
                ->references(Employee::F_ID)
                ->on(Employee::TABLE_NAME)
                ->onUpdate('cascade');
            $table->foreign(WorkLog::F_ROOM_ID)
                ->references(Room::F_ID)
                ->on(Room::TABLE_NAME)
                ->onUpdate('cascade');
            $table->char(WorkLog::F_TYPE_OF_CLEANING, 1);
            $table->timestamp(WorkLog::F_TIME_START)->nullable(TRUE);
            $table->timestamp(WorkLog::F_TIME_END)->nullable(TRUE);
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
        Schema::dropIfExists(WorkLog::TABLE_NAME);
    }
}
