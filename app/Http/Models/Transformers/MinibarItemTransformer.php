<?php

namespace App\Http\Models\Transformers;

use App\Http\Models\MinibarItem;
use League\Fractal\TransformerAbstract;

class MinibarItemTransformer extends TransformerAbstract
{
    /**
     * @param MinibarItem|null $minibarItem
     *
     * @return array
     */
    public function transform(MinibarItem $minibarItem = NULL): array
    {
        if ($minibarItem) {
            $response = [
                MinibarItem::F_ID          => (int)$minibarItem->{MinibarItem::F_ID},
                MinibarItem::F_CUSTOMER_ID => (int)$minibarItem->{MinibarItem::F_CUSTOMER_ID},
                MinibarItem::F_NAME        => (string)$minibarItem->{MinibarItem::F_NAME},
                MinibarItem::F_AMOUNT      => (int)$minibarItem->{MinibarItem::F_AMOUNT},
                MinibarItem::F_PICTURE     => (array)$minibarItem->{MinibarItem::F_PICTURE},
            ];
        }
        return $response ?? [];
    }
}
