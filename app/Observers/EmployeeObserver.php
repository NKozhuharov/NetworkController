<?php

namespace App\Observers;

use App\Http\Models\Employee;
use App\Http\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Nevestul4o\NetworkController\Models\BaseModel;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     *
     * @param Employee $employee
     * @return void
     */
    public function created(Employee $employee)
    {
        $user = new User();
        $user->{User::F_NAME} = $employee->{Employee::F_EMAIL};
        $user->{User::F_EMAIL} = $employee->{Employee::F_EMAIL};
        $user->{User::F_PASSWORD} = Hash::make(User::DEFAULT_PASSWORD);
        $user->{User::F_TYPE} = User::TYPE_EMPLOYEE;
        $user->save();

        $employee->{Employee::F_USER_ID} = $user->{BaseModel::F_ID};
        $employee->save();
    }

    /**
     * Handle the Employee "updated" event.
     *
     * @param Employee $employee
     * @return void
     */
    public function updated(Employee $employee)
    {
        if (!$employee->wasChanged(Employee::F_EMAIL)) {
            return;
        }

        /** @var User $user */
        $user = User::findOrFail($employee->{Employee::F_USER_ID});
        $user->{User::F_NAME} = $employee->{Employee::F_EMAIL};
        $user->{User::F_EMAIL} = $employee->{Employee::F_EMAIL};
        $user->save();
    }

    /**
     * Handle the Employee "deleted" event.
     *
     * @param Employee $employee
     * @return void
     * @throws Exception
     */
    public function deleted(Employee $employee)
    {
        /** @var User $user */
        $user = User::findOrFail($employee->{Employee::F_USER_ID});
        $user->delete();
    }
}
