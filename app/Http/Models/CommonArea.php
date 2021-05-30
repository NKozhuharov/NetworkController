<?php

namespace App\Http\Models;

class CommonArea extends RoomType
{
    const STATIC_DATA_IDENTIFIER = 'common_area';
    const TYPE_COMMON_AREA = 'Common area';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->{self::F_NAME} = self::TYPE_COMMON_AREA;
    }
}
