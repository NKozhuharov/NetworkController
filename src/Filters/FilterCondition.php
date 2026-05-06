<?php

namespace Nevestul4o\NetworkController\Filters;

class FilterCondition
{
    public function __construct(public readonly string $column, public readonly FilterOperators $operator, public readonly mixed $value)
    {
    }

    public function isRelationFilter(): bool
    {
        return in_array($this->operator, [
            FilterOperators::FILTER_HAS,
            FilterOperators::FILTER_DOESNT_HAVE
        ]);
    }

    public function isColumnFilter(): bool
    {
        return !$this->isRelationFilter();
    }
}