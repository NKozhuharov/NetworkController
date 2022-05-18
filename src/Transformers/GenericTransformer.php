<?php

namespace Nevestul4o\NetworkController\Transformers;

use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\Models\BaseModel;

class GenericTransformer extends TransformerAbstract
{
    /**
     * A simple transformer, which converts a Model into an array
     *
     * @param  BaseModel|null  $model
     * @return array
     */
    public function transform(BaseModel $model = null): array
    {
        if (!$model) {
            return [];
        }

        return $model->toArray();
    }
}
