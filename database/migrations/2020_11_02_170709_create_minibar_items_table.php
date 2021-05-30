<?php

use App\Http\Models\Customer;
use App\Http\Models\MinibarItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMinibarItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(MinibarItem::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(MinibarItem::F_CUSTOMER_ID);
            $table->foreign(MinibarItem::F_CUSTOMER_ID)->references(Customer::F_ID)
                ->on(Customer::TABLE_NAME)->onUpdate('cascade');
            $table->string(MinibarItem::F_NAME);
            $table->integer(MinibarItem::F_AMOUNT)->default(0)->nullable(FALSE);
            $table->json(MinibarItem::F_PICTURE)->nullable();
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
        Schema::dropIfExists(MinibarItem::TABLE_NAME);
    }
}
