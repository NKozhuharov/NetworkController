<?php

namespace Nevestul4o\NetworkController\Filters;

enum FilterOperators: string
{
    case EQUALS = '=';
    case FILTER_NOT = 'not';
    case FILTER_GREATER_THAN = 'gt';
    case FILTER_LESSER_THAN = 'lt';
    case FILTER_GREATER_THAN_OR_EQUALS = 'gte';
    case FILTER_LESSER_THAN_OR_EQUALS = 'lte';
    case FILTER_FULL_MATCH = '%%';
    case FILTER_RIGHT_MATCH = '*%';
    case FILTER_LEFT_MATCH = '%*';
    case FILTER_IN = 'in';
    case FILTER_NOT_IN = 'notin';
    case FILTER_HAS = 'has';
    case FILTER_DOESNT_HAVE = 'doesnthave';

    public function toQueryOperator(): string
    {
        return match ($this) {
            self::FILTER_NOT => '!=',
            self::FILTER_GREATER_THAN => '>',
            self::FILTER_LESSER_THAN => '<',
            self::FILTER_GREATER_THAN_OR_EQUALS => '>=',
            self::FILTER_LESSER_THAN_OR_EQUALS => '<=',
            self::FILTER_FULL_MATCH,
            self::FILTER_RIGHT_MATCH,
            self::FILTER_LEFT_MATCH => 'LIKE',
            self::FILTER_IN => 'IN',
            self::FILTER_NOT_IN => 'NOT IN',
            default => '=',
        };
    }
}
