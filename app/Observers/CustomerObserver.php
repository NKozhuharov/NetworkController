<?php

namespace App\Observers;

use App\Http\Models\CommonArea;
use Nevestul4o\NetworkController\Models\BaseModel;
use App\Http\Models\Customer;
use App\Http\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;

class CustomerObserver
{
    /**
     * Handle the customer "created" event.
     *
     * @param Customer $customer
     * @return void
     */
    public function created(Customer $customer)
    {
        $roomTypeCommonArea = new CommonArea();
        $roomTypeCommonArea->{CommonArea::F_CUSTOMER_ID} = $customer->{BaseModel::F_ID};
        $roomTypeCommonArea->save();

        $user = new User();
        $user->{User::F_NAME} = $customer->{Customer::F_EMAIL};
        $user->{User::F_EMAIL} = $customer->{Customer::F_EMAIL};
        $user->{User::F_PASSWORD} = Hash::make(User::DEFAULT_PASSWORD);
        $user->{User::F_TYPE} = User::TYPE_CUSTOMER;
        $user->save();

        $customer->{Customer::F_USER_ID} = $user->{BaseModel::F_ID};
        $customer->save();
    }

    /**
     * Handle the Customer "updated" event.
     *
     * @param Customer $customer
     * @return void
     */
    public function updated(Customer $customer)
    {
        if (!$customer->wasChanged(Customer::F_EMAIL)) {
            return;
        }

        /** @var User $user */
        $user = User::findOrFail($customer->{Customer::F_USER_ID});
        $user->{User::F_NAME} = $customer->{Customer::F_EMAIL};
        $user->{User::F_EMAIL} = $customer->{Customer::F_EMAIL};
        $user->save();
    }

    /**
     * Handle the Customer "deleted" event.
     *
     * @param Customer $customer
     * @return void
     * @throws Exception
     */
    public function deleted(Customer $customer)
    {
        /** @var User $user */
        $user = User::findOrFail($customer->{Customer::F_USER_ID});
        $user->delete();
    }
}
