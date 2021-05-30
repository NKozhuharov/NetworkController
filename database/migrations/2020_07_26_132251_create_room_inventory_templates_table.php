<?php

use App\Http\Models\Customer;
use App\Http\Models\RoomInventoryTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomInventoryTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(RoomInventoryTemplate::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(RoomInventoryTemplate::F_CUSTOMER_ID);
            $table->foreign(RoomInventoryTemplate::F_CUSTOMER_ID)->references(Customer::F_ID)
                ->on(Customer::TABLE_NAME)->onUpdate('cascade');
            $table->string(RoomInventoryTemplate::F_NAME);
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
        Schema::dropIfExists(RoomInventoryTemplate::TABLE_NAME);
    }
}
