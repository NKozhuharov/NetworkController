<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\CustomerContact;
use League\Fractal\TransformerAbstract;

class CustomerContactTransformer extends TransformerAbstract
{
    /**
     * @param CustomerContact|null $customerContact
     *
     * @return array
     */
    public function transform(CustomerContact $customerContact = NULL): array
    {
        if ($customerContact) {
            $response = [
                CustomerContact::F_ID          => (int)$customerContact->{CustomerContact::F_ID},
                CustomerContact::F_CUSTOMER_ID => (int)$customerContact->{CustomerContact::F_CUSTOMER_ID},
                CustomerContact::F_TITLE       => (string)$customerContact->{CustomerContact::F_TITLE},
                CustomerContact::F_NAME        => (string)$customerContact->{CustomerContact::F_NAME},
                CustomerContact::F_PHONE       => (string)$customerContact->{CustomerContact::F_PHONE},
                CustomerContact::F_EMAIL       => (string)$customerContact->{CustomerContact::F_EMAIL},
            ];
        }
        return $response ?? [];
    }
}
