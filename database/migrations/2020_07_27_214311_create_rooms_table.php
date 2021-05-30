<?php

use App\Http\Models\Customer;
use App\Http\Models\Room;
use App\Http\Models\RoomInventoryTemplate;
use App\Http\Models\RoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Room::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(Room::F_CUSTOMER_ID);
            $table->unsignedBigInteger(Room::F_ROOM_TYPE_ID);
            $table->unsignedBigInteger(Room::F_ROOM_INVENTORY_TEMPLATE_ID)->nullable(TRUE);
            $table->foreign(Room::F_CUSTOMER_ID)
                ->references(Customer::F_ID)
                ->on(Customer::TABLE_NAME)
                ->onUpdate('cascade');
            $table->foreign(Room::F_ROOM_TYPE_ID)
                ->references(RoomType::F_ID)
                ->on(RoomType::TABLE_NAME)
                ->onUpdate('cascade');
            $table->foreign(Room::F_ROOM_INVENTORY_TEMPLATE_ID)
                ->references(RoomInventoryTemplate::F_ID)
                ->on(RoomInventoryTemplate::TABLE_NAME)
                ->onUpdate('cascade');
            $table->string(Room::F_NAME, 50);
            $table->tinyInteger(Room::F_FLOOR);
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
        Schema::dropIfExists(Room::TABLE_NAME);
    }
}
