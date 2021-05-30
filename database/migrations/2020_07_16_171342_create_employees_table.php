<?php

use App\Http\Models\Country;
use App\Http\Models\Employee;
use App\Http\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Nevestul4o\NetworkController\Models\BaseModel;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Employee::TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->string(Employee::F_NAME);
            $table->string(Employee::F_SECOND_NAME);
            $table->string(Employee::F_SURNAME);
            $table->string(Employee::F_EGN, 10);
            $table->string(Employee::F_ID_NUMBER, 9);
            $table->char(Employee::F_GENDER, 1);
            $table->date(Employee::F_DATE_OF_BIRTH);
            $table->unsignedBigInteger(Employee::F_COUNTRY_ID);
            $table->foreign(Employee::F_COUNTRY_ID)->references(BaseModel::F_ID)
                ->on(Country::TABLE_NAME)->onUpdate('cascade');
            $table->string(Employee::F_ADDRESS);
            $table->date(Employee::F_ID_ISSUED_ON);
            $table->date(Employee::F_ID_EXPIRES_ON);
            $table->string(Employee::F_PHONE)->nullable();
            $table->string(Employee::F_EMAIL)->unique()->nullable();
            $table->char(Employee::F_TYPE, 1);
            $table->char(Employee::F_STATUS, 1);
            $table->json(Employee::F_FILES)->nullable();
            $table->unsignedBigInteger(Employee::F_USER_ID)->nullable(TRUE)->default(NULL);
            $table->foreign(Employee::F_USER_ID)->references(BaseModel::F_ID)
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
        Schema::dropIfExists(Employee::TABLE_NAME);
    }
}
