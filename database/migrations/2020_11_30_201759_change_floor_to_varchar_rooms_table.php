<?php

use App\Http\Models\Room;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFloorToVarcharRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Room::TABLE_NAME, function (Blueprint $table) {
            $table->string(Room::F_FLOOR, 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Room::TABLE_NAME, function (Blueprint $table) {
            $table->tinyInteger(Room::F_FLOOR)->change();
        });
    }
}
