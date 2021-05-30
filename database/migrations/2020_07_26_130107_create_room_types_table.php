<?php

use App\Http\Models\Customer;
use App\Http\Models\RoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(RoomType::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(RoomType::F_CUSTOMER_ID);
            $table->foreign(RoomType::F_CUSTOMER_ID)->references(Customer::F_ID)
                ->on(Customer::TABLE_NAME)->onUpdate('cascade');
            $table->string(RoomType::F_NAME);
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
        Schema::dropIfExists(RoomType::TABLE_NAME);
    }
}
