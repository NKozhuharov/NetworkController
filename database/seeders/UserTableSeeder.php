<?php

namespace Database\Seeders;

use App\Http\Models\User;
use Illuminate\Support\Facades\Hash;
use Nevestul4o\NetworkController\Database\BaseSeeder;

class UserTableSeeder extends BaseSeeder
{
    public function __construct()
    {
        $this->objects[] = [
            User::F_NAME              => 'nikolakozhuharovv@gmail.com',
            User::F_PASSWORD          => Hash::make(User::DEFAULT_PASSWORD),
            User::F_EMAIL_VERIFIED_AT => time(),
            User::F_EMAIL             => 'nikolakozhuharovv@gmail.com',
            User::F_TYPE              => User::TYPE_ADMIN,
        ];

        $this->objects[] = [
            User::F_NAME              => 'h.ivanov@infracleaning.com',
            User::F_PASSWORD          => Hash::make(User::DEFAULT_PASSWORD),
            User::F_EMAIL_VERIFIED_AT => time(),
            User::F_EMAIL             => 'h.ivanov@infracleaning.com',
            User::F_TYPE              => User::TYPE_ADMIN,
        ];

        $this->objects[] = [
            User::F_NAME              => 'd.damyanov@netcube.eu',
            User::F_PASSWORD          => Hash::make(User::DEFAULT_PASSWORD),
            User::F_EMAIL_VERIFIED_AT => time(),
            User::F_EMAIL             => 'd.damyanov@netcube.eu',
            User::F_TYPE              => User::TYPE_ADMIN,
        ];

        $this->objects[] = [
            User::F_NAME              => 'yoan.ivanov13@gmail.com',
            User::F_PASSWORD          => Hash::make(User::DEFAULT_PASSWORD),
            User::F_EMAIL_VERIFIED_AT => time(),
            User::F_EMAIL             => 'yoan.ivanov13@gmail.com',
            User::F_TYPE              => User::TYPE_ADMIN,
        ];
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        foreach ($this->objects as $object) {
            $this->insertedObjects[] = User::create($object);
        }
    }
}
