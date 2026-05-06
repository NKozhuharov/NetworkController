<?php

namespace Nevestul4o\NetworkController\Filters;

use Illuminate\Database\Eloquent\Builder;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Translation\TranslationService;

class QueryFilterService
{
    public function __construct(protected TranslationService $translationService)
    {
    }

    public function applyFilters(Builder $builder, BaseModel $model, array $filters): Builder
    {
        [$relationFilters, $columnFilters] = $this->splitFilters($this->normalizeFilters($filters));

        // 1. COLUMN PATH FILTERS (recursive whereHas)
        foreach ($this->groupFiltersByPath($columnFilters) as $path => $conditions) {
            $segments = $path === '' ? [] : explode('.', $path);

            $this->applyGroupedNestedFilter(
                $builder,
                $model,
                $segments,
                $conditions
            );
        }

        // 2. RELATION FILTERS (has / doesntHave)
        foreach ($relationFilters as $filter) {
            $this->applyRelationFilter(
                $model,
                $builder,
                $filter->column,
                $filter->operator,
                $filter->value,
            );
        }

        return $builder;
    }

    /**
     * SPLIT relation vs column filters
     * @param array<FilterCondition> $filters
     * @return array
     */
    protected function splitFilters(array $filters): array
    {
        $relation = [];
        $columns = [];

        foreach ($filters as $filter) {
            if ($filter->isRelationFilter()) {
                $relation[] = $filter;
                continue;
            }

            $columns[] = $filter;
        }

        return [$relation, $columns];
    }

    protected function normalizeFilters(array $filters): array
    {
        $result = [];

        foreach ($filters as $filterKey => $filterValue) {
            // simple case: filters[key]=value
            if (!is_array($filterValue)) {
                $result[] = new FilterCondition($filterKey, FilterOperators::EQUALS, $filterValue);
                continue;
            }

            //complex case: filters[key][operator]=value
            foreach ($filterValue as $requestOperator => $value) {
                $requestOperator = FilterOperators::tryFrom($requestOperator);
                if (!$requestOperator) {
                    continue;
                }

                if (in_array($requestOperator, [FilterOperators::FILTER_HAS, FilterOperators::FILTER_DOESNT_HAVE], true)) {
                    $result[] = new FilterCondition($filterKey, $requestOperator, $value);
                    continue 2;
                }

                if (in_array($requestOperator, [FilterOperators::FILTER_FULL_MATCH, FilterOperators::FILTER_RIGHT_MATCH, FilterOperators::FILTER_LEFT_MATCH], true)) {
                    $value = $this->getFilterLikeSearchWordValue($value, $requestOperator);
                } elseif (in_array($requestOperator, [FilterOperators::FILTER_IN, FilterOperators::FILTER_NOT_IN])) {
                    $value = explode(',', $value);
                }

                $result[] = new FilterCondition($filterKey, $requestOperator, $value);
            }
        }

        return $result;
    }

    /**
     * @param array<FilterCondition> $filters
     * @return array
     */
    protected function groupFiltersByPath(array $filters): array
    {
        $grouped = [];

        foreach ($filters as $filter) {
            $segments = explode('.', $filter->column);

            $column = array_pop($segments);
            $path = implode('.', $segments);

            $grouped[$path][] = new FilterCondition($column, $filter->operator, $filter->value);
        }

        return $grouped;
    }

    protected function applyGroupedNestedFilter(
        $builder,
        $model,
        array $segments,
        array $conditions
    ): void {
        // FINAL level → apply all conditions together
        if (empty($segments)) {
            foreach ($conditions as $condition) {
                if (!in_array($condition->column, $model->getFilterable(), true)) {
                    continue;
                }

                $this->translationService->joinTranslationModelTableIfNecessary(
                    $model,
                    $condition->column,
                    $builder
                );

                $this->applyCondition($model, $builder, $condition);
            }

            return;
        }

        // RELATION step
        $relation = array_shift($segments);

        if (!in_array($relation, $model->getResolveAble(), true)) {
            return;
        }

        $builder->whereHas($relation, function ($innerBuilder) use (
            $model,
            $relation,
            $segments,
            $conditions
        ) {
            $relatedModel = $model->{$relation}()->getRelated();

            $this->applyGroupedNestedFilter(
                $innerBuilder,
                $relatedModel,
                $segments,
                $conditions
            );
        });
    }

    protected function applyCondition($model, $builder, FilterCondition $condition): void
    {
        switch ($condition->operator) {
            case FilterOperators::FILTER_IN:
                $column = $condition->column;
                if ($column === 'id') {
                    $column = $model->getQualifiedKeyName();
                }

                $builder->whereIn($column, $condition->value);
                break;

            case FilterOperators::FILTER_NOT_IN:
                $column = $condition->column;
                if ($column === 'id') {
                    $column = $model->getQualifiedKeyName();
                }

                $builder->whereNotIn($column, $condition->value);
                break;

            case FilterOperators::FILTER_HAS:
            case FilterOperators::FILTER_DOESNT_HAVE:
                $this->applyRelationFilter($model, $builder, $condition->column, $condition->value, $condition->operator);
                break;

            default:
                $builder->where($condition->column, $condition->operator->toQueryOperator(), $condition->value);
                break;
        }
    }

    /**
     * Applies a relational filter to the query builder by modifying its conditions based on the given relation, filter value, and operator.
     *
     * @param BaseModel $model
     * @param mixed $builder The query builder instance that will be modified.
     * @param string $relation The name of the relation to filter.
     * @param FilterOperators $filterOperator The operator defining the type of filter to apply (e.g., has or does not have the relation).
     * @param mixed $filterValue The value used for filtering the results in the specified relation.
     *
     * @return void
     */
    protected function applyRelationFilter(BaseModel $model, mixed &$builder, string $relation, FilterOperators $filterOperator, mixed $filterValue): void
    {
        if (!in_array($relation, $model->getFillableRelations(), TRUE)) {
            return;
        }

        if ($filterOperator === FilterOperators::FILTER_HAS) {
            if (empty($filterValue)) {
                $builder->whereHas($relation);
                return;
            }

            $builder->whereHas($relation, function ($innerBuilder) use ($model, $relation, $filterValue) {
                $innerBuilder->where($model->{$relation}()->getRelated()->getTable() . '.' . $model->{$relation}()->getRelated()->getKeyName(), $filterValue);
            });

            return;
        }

        if ($filterOperator === FilterOperators::FILTER_DOESNT_HAVE) {
            if (empty($filterValue)) {
                $builder->whereDoesntHave($relation);
                return;
            }

            $builder->whereDoesntHave($relation, function ($innerBuilder) use ($model, $relation, $filterValue) {
                $innerBuilder->where($model->{$relation}()->getRelated()->getTable() . '.' . $model->{$relation}()->getRelated()->getKeyName(), $filterValue);
            });
        }
    }

    /**
     * Prepares a string for filtering, according to the pre-defined match operators
     *
     * @param string $searchWord
     * @param FilterOperators $operator
     * @return string
     */
    public function getFilterLikeSearchWordValue(string $searchWord, FilterOperators $operator): string
    {
        return match ($operator) {
            FilterOperators::FILTER_RIGHT_MATCH => mb_strtolower($searchWord) . '%',
            FilterOperators::FILTER_LEFT_MATCH => '%' . mb_strtolower($searchWord),
            default => '%' . mb_strtolower($searchWord) . '%',
        };
    }
}
