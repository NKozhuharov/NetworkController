<?php

namespace App\Http\Models;
use Nevestul4o\NetworkController\Models\BaseModel;

abstract class TypesOfCleaning extends BaseModel
{
    const F_TYPE_OF_CLEANING = 'type_of_cleaning';

    const TYPE_OF_CLEANING_DEPARTURE = 'D';
    const TYPE_OF_CLEANING_STAY_OVER = 'S';
    const TYPE_OF_CLEANING_CLEANING = 'C';

    const TYPES_OF_CLEANING = [
        'Основно почистване'  => self::TYPE_OF_CLEANING_DEPARTURE,
        'Междинно почистване' => self::TYPE_OF_CLEANING_STAY_OVER,
        'Почистване'          => self::TYPE_OF_CLEANING_CLEANING,
    ];
}
