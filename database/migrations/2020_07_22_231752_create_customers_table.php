<?php

use App\Http\Models\Country;
use App\Http\Models\Customer;
use App\Http\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nevestul4o\NetworkController\Models\BaseModel;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Customer::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string(Customer::F_NAME);
            $table->string(Customer::F_EMAIL);
            $table->unsignedBigInteger(Customer::F_COUNTRY_ID);
            $table->foreign(Customer::F_COUNTRY_ID)->references(Country::F_ID)
                ->on(Country::TABLE_NAME)->onUpdate('cascade');
            $table->string(Customer::F_TOWN);
            $table->string(Customer::F_ADDRESS);
            $table->unsignedBigInteger(Customer::F_USER_ID)->nullable(TRUE)->default(NULL);
            $table->foreign(Customer::F_USER_ID)->references(BaseModel::F_ID)
                ->on(User::TABLE_NAME)->onUpdate('cascade');
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
        Schema::dropIfExists(Customer::TABLE_NAME);
    }
}
