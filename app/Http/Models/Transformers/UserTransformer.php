<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\User;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\Models\BaseModel;

class UserTransformer extends TransformerAbstract
{
    /**
     * @param User|null $user
     *
     * @return array
     */
    public function transform($user = NULL): array
    {
        if ($user) {
            if ($user->isEmployee()) {
                $this->setDefaultIncludes([User::FR_EMPLOYEE]);
            } elseif ($user->isCustomer()) {
                $this->setDefaultIncludes([User::FR_CUSTOMER]);
            }

            $response = [
                BaseModel::F_ID           => (int)$user->{BaseModel::F_ID},
                User::F_EMAIL             => (string)$user->{User::F_EMAIL},
                User::F_NAME              => (string)$user->{User::F_NAME},
                User::F_TYPE              => (string)$user->{User::F_TYPE},
                User::F_EMAIL_VERIFIED_AT => $user->{User::F_EMAIL_VERIFIED_AT} ? Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $user->{User::F_EMAIL_VERIFIED_AT}
                )->format('U') : NULL,
            ];
        }

        return $response ?? [];
    }

    public function includeEmployee(User $user)
    {
        return $user->employee()
            ? $this->item($user->{User::FR_EMPLOYEE}, new EmployeeTransformer())
            : $this->null();
    }

    public function includeCustomer(User $user)
    {
        return $user->customer()
            ? $this->item($user->{User::FR_CUSTOMER}, new CustomerTransformer())
            : $this->null();
    }
}

