<?php

use App\Http\Models\RoomInventoryTemplateItems;
use App\Http\Models\RoomInventoryTemplate;
use App\Http\Models\RoomItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomInventoryTemplateItemsTable extends Migration
{
    const FK_1 = 'fk1';
    const FK_2 = 'fk2';
    const UNIQUE = 'unique';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(RoomInventoryTemplateItems::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(RoomInventoryTemplateItems::F_ROOM_INVENTORY_TEMPLATE_ID);
            $table->foreign(RoomInventoryTemplateItems::F_ROOM_INVENTORY_TEMPLATE_ID, self::FK_1)
                ->references(RoomInventoryTemplate::F_ID)
                ->on(RoomInventoryTemplate::TABLE_NAME)
                ->onUpdate('cascade');
            $table->unsignedBigInteger(RoomInventoryTemplateItems::F_ROOM_ITEM_ID);
            $table->foreign(RoomInventoryTemplateItems::F_ROOM_ITEM_ID, self::FK_2)
                ->references(RoomItem::F_ID)
                ->on(RoomItem::TABLE_NAME)
                ->onUpdate('cascade');
            $table->unique(
                [RoomInventoryTemplateItems::F_ROOM_INVENTORY_TEMPLATE_ID, RoomInventoryTemplateItems::F_ROOM_ITEM_ID],
                self::UNIQUE
            );
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
        Schema::dropIfExists(RoomInventoryTemplateItems::TABLE_NAME);
    }
}
