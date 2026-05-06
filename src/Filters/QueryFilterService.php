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

    public function applyFilters(
        Builder   $builder,
        BaseModel $model,
        array     $filters
    ): Builder {
        foreach ($this->groupFiltersByPath($this->normalizeFilters($filters)) as $path => $conditions) {
            $segments = $path === '' ? [] : explode('.', $path);

            $this->applyGroupedNestedFilter(
                $builder,
                $model,
                $segments,
                $conditions
            );
        }

        return $builder;
    }

    protected function normalizeFilters(array $filters): array
    {
        $result = [];

        foreach ($filters as $filterKey => $filterValue) {
            // simple case: filters[key]=value
            if (!is_array($filterValue)) {
                $result[] = [
                    'key'      => $filterKey,
                    'operator' => '=',
                    'value'    => $filterValue,
                ];
                continue;
            }

            //complex case: filters[key][operator]=value
            foreach ($filterValue as $requestOperator => $value) {
                $requestOperator = FilterOperators::tryFrom($requestOperator);
                if (!$requestOperator) {
                    continue;
                }

                if (in_array($requestOperator, [FilterOperators::FILTER_HAS, FilterOperators::FILTER_DOESNT_HAVE], true)) {
                    $result[] = [
                        'key'      => $filterKey,
                        'operator' => $requestOperator->value,
                        'value'    => $value,
                    ];
                    continue 2;
                }
                if (in_array($requestOperator, [FilterOperators::FILTER_FULL_MATCH, FilterOperators::FILTER_RIGHT_MATCH, FilterOperators::FILTER_LEFT_MATCH], true)) {
                    $value = $this->getFilterLikeSearchWordValue($value, $requestOperator);
                }

                $operator = $requestOperator->toQueryOperator();

                $result[] = [
                    'key'      => $filterKey,
                    'operator' => $operator,
                    'value'    => $value,
                ];
            }
        }

        return $result;
    }

    protected function groupFiltersByPath(array $filters): array
    {
        $grouped = [];

        foreach ($filters as $filter) {
            // HAS / DOESNT_HAVE are special → no grouping
            if (in_array($filter['operator'], [
                FilterOperators::FILTER_HAS->value,
                FilterOperators::FILTER_DOESNT_HAVE->value
            ], true)) {
                $grouped['__relations'][] = $filter;
                continue;
            }

            $segments = explode('.', $filter['key']);

            $column = array_pop($segments);
            $path = implode('.', $segments);

            $grouped[$path][] = [
                'column'   => $column,
                'operator' => $filter['operator'],
                'value'    => $filter['value'],
            ];
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
                if (!in_array($condition['column'], $model->getFilterable(), true)) {
                    continue;
                }

                $this->translationService->joinTranslationModelTableIfNecessary(
                    $model,
                    $condition['column'],
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

    protected function applyCondition($model, $builder, array $condition): void
    {
        $column = $condition['column'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        switch ($operator) {
            case 'IN':
                $builder->whereIn($column, $value);
                break;

            case 'NOT IN':
                $builder->whereNotIn($column, $value);
                break;

            case FilterOperators::FILTER_HAS->value:
            case FilterOperators::FILTER_DOESNT_HAVE->value:
                $this->applyRelationFilter($model, $builder, $column, $value, $operator);
                break;

            default:
                $builder->where($column, $operator, $value);
                break;
        }
    }

    /**
     * Applies a relational filter to the query builder by modifying its conditions based on the given relation, filter value, and operator.
     *
     * @param BaseModel $model
     * @param mixed $builder The query builder instance that will be modified.
     * @param string $relation The name of the relation to filter.
     * @param string|null $filterValue The value used for filtering the results in the specified relation.
     * @param string $filterOperator The operator defining the type of filter to apply (e.g., has or does not have the relation).
     *
     * @return void
     */
    protected function applyRelationFilter(BaseModel $model, mixed &$builder, string $relation, string|null $filterValue, string $filterOperator): void
    {
        if (!in_array($relation, $model->getFillableRelations(), TRUE)) {
            return;
        }

        switch ($filterOperator) {
            case FilterOperators::FILTER_HAS->value:
                if (empty($filterValue)) {
                    $builder->whereHas($relation);
                    return;
                }

                $builder->whereHas($relation, function ($innerBuilder) use ($model, $relation, $filterValue) {
                    $innerBuilder->where($model->{$relation}()->getRelated()->getTable() . '.' . $model->{$relation}()->getRelated()->getKeyName(), $filterValue);
                });

                return;
            case FilterOperators::FILTER_DOESNT_HAVE->value:
                if (empty($filterValue)) {
                    $builder->whereDoesntHave($relation);
                    return;
                }

                $builder->whereDoesntHave($relation, function ($innerBuilder) use ($model, $relation, $filterValue) {
                    $innerBuilder->where($model->{$relation}()->getRelated()->getTable() . '.' . $model->{$relation}()->getRelated()->getKeyName(), $filterValue);
                });

                return;
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