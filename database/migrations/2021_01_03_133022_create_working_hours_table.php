<?php

use App\Http\Models\WorkingHours;
use App\Http\Models\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkingHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(WorkingHours::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(WorkingHours::F_EMPLOYEE_ID);
            $table->foreign(WorkingHours::F_EMPLOYEE_ID)
                ->references(Employee::F_ID)
                ->on(Employee::TABLE_NAME)
                ->onUpdate('cascade');
            $table->char(WorkingHours::F_TYPE, 1);
            $table->timestamp(WorkingHours::F_INTERVAL_START)->nullable(TRUE);
            $table->timestamp(WorkingHours::F_INTERVAL_END)->nullable(TRUE);
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
        Schema::dropIfExists(WorkingHours::TABLE_NAME);
    }
}
