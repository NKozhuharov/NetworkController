<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\Customer;
use League\Fractal\TransformerAbstract;

class CustomerTransformer extends TransformerAbstract
{
    /**
     * @param Customer|null $customer
     *
     * @return array
     */
    public function transform(Customer $customer = NULL): array
    {
        if ($customer) {
            $response = [
                Customer::F_ID         => (int)$customer->{Customer::F_ID},
                Customer::F_NAME       => (string)$customer->{Customer::F_NAME},
                Customer::F_EMAIL      => (string)$customer->{Customer::F_EMAIL},
                Customer::F_COUNTRY_ID => (int)$customer->{Customer::F_COUNTRY_ID},
                Customer::F_TOWN       => (string)$customer->{Customer::F_TOWN},
                Customer::F_ADDRESS    => (string)$customer->{Customer::F_ADDRESS},
                Customer::F_USER_ID    => (int)$customer->{Customer::F_USER_ID},
            ];
        }
        return $response ?? [];
    }

    public function includeUser(Customer $customer)
    {
        return $customer->user()
            ? $this->item($customer->{Customer::FR_USER}, new UserTransformer())
            : $this->null();
    }
}
