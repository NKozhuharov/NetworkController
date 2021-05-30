<?php

use App\Http\Models\Customer;
use App\Http\Models\CustomerContact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(CustomerContact::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger(CustomerContact::F_CUSTOMER_ID);
            $table->foreign(CustomerContact::F_CUSTOMER_ID)->references(Customer::F_ID)
                ->on(Customer::TABLE_NAME)->onUpdate('cascade');
            $table->char(CustomerContact::F_TITLE, 1);
            $table->string(CustomerContact::F_NAME);
            $table->string(CustomerContact::F_EMAIL);
            $table->string(CustomerContact::F_PHONE);
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
        Schema::dropIfExists(CustomerContact::TABLE_NAME);
    }
}
