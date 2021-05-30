<?php

use App\Http\Models\MinibarItem;
use App\Http\Models\MinibarItemMovement;
use App\Http\Models\Room;
use App\Http\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nevestul4o\NetworkController\Models\BaseModel;

class CreateMinibarItemMovementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(MinibarItemMovement::TABLE_NAME, function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger(MinibarItemMovement::F_MINIBAR_ITEM_ID);
            $table->foreign(MinibarItemMovement::F_MINIBAR_ITEM_ID)->references(MinibarItem::F_ID)
                ->on(MinibarItem::TABLE_NAME)->onUpdate('cascade');
            $table->unsignedBigInteger(MinibarItemMovement::F_USER_ID);
            $table->foreign(MinibarItemMovement::F_USER_ID)->references(BaseModel::F_ID)
                ->on(User::TABLE_NAME)->onUpdate('cascade');
            $table->unsignedBigInteger(MinibarItemMovement::F_ROOM_ID)->nullable();
            $table->foreign(MinibarItemMovement::F_ROOM_ID)->references(Room::F_ID)
                ->on(Room::TABLE_NAME)->onUpdate('cascade');
            $table->integer(MinibarItemMovement::F_MOVEMENT);
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
        Schema::dropIfExists(MinibarItemMovement::TABLE_NAME);
    }
}
