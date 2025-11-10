<?php

namespace Nevestul4o\NetworkController;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceAbstract;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\Models\BaseModel;
use Nevestul4o\NetworkController\Transformers\Interface\NestedIncludesTransformer as NestedIncludesInterface;

abstract class NetworkController extends BaseController
{
    use ValidatesRequests;

    const ORDER_BY_PARAM = 'orderby';
    const SORT_PARAM = 'sort';
    const LIMIT_PARAM = 'limit';
    const QUERY_PARAM = 'query';
    const FILTERS_PARAM = 'filters';
    const RESOLVE_PARAM = 'resolve';
    const SHOW_META_PARAM = 'showMeta';
    const AGGREGATE_PARAM = 'aggregate';
    const SLUG_PARAM = 'slug';
    const META_ROUTE_INFO_PARAM = 'route_info';

    const ONLY_DELETED = 'only_deleted';
    const WITH_DELETED = 'with_deleted';

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const LIMIT_ALL = 'all';
    const LIMIT_EMPTY = 'empty';

    const FILTER_NOT = 'not';
    const FILTER_GREATER_THAN = 'gt';
    const FILTER_LESSER_THAN = 'lt';
    const FILTER_GREATER_THAN_OR_EQUALS = 'gte';
    const FILTER_LESSER_THAN_OR_EQUALS = 'lte';
    const FILTER_FULL_MATCH = '%%';
    const FILTER_RIGHT_MATCH = '*%';
    const FILTER_LEFT_MATCH = '%*';
    const FILTER_IN = 'in';
    const FILTER_NOT_IN = 'notin';
    const FILTER_HAS = 'has';
    const FILTER_DOESNT_HAVE = 'doesnthave';

    /**
     * The class of the model
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * An instance of the model. Allows overriding of the model with a Builder to apply commands to the Index function
     *
     * @var Builder|BaseModel
     */
    protected Builder|BaseModel $model;

    /**
     * If we are using a transformer, create an instance here
     *
     * @var TransformerAbstract
     */
    protected TransformerAbstract $transformerInstance;

    /**
     * How many items to show per page
     *
     * @var int
     */
    protected int $itemsPerPage = 20;

    /**
     * Ignore the second validation when using parent:: method
     *
     * @var bool
     */
    protected bool $isValidated = FALSE;

    /**
     * Ignore additional relation setter
     *
     * @var bool
     */
    protected bool $withoutRelationInsert = FALSE;

    /**
     * The default column to order collections by
     *
     * @var string
     */
    protected string $defaultOrder = BaseModel::F_ID;

    /**
     * The default direction to sort collections by
     *
     * @var string
     */
    protected string $defaultSort = self::SORT_DESC;

    /**
     * Contains all columns, which can be used to order a collection
     *
     * @var array
     */
    protected array $orderAble;

    /**
     * Contains all columns, which can be used to filter a collection
     *
     * @var array
     */
    protected array $filterAble;

    /**
     * Contains all relationship definitions, which can be used to filter a collection
     *
     * @var array
     */
    protected array $filterAbleRelations;

    /**
     * An array of filters, which will always be applied
     *
     * @var array
     */
    protected array $defaultFilters = [];

    /**
     * Contains all related models, which can be resolved when getting a collection
     *
     * @var array
     */
    protected array $resolveAble;

    /**
     * An array of relations, which will always be resolved
     *
     * @var array
     */
    protected array $defaultResolve = [];

    /**
     * Contains all columns, which can be used to query a collection
     *
     * @var array
     */
    protected array $queryAble;

    /**
     * Contains the function names, which can be used to aggregate a collection
     *
     * @var array
     */
    protected array $aggregateAble;

    /**
     * An instance of ResponseHelper
     *
     * @var JsonResponseHelper
     */
    protected JsonResponseHelper $responseHelper;

    /**
     * Create a new controller instance.
     *
     * @throws Exception
     */
    public function __construct()
    {
        if (empty($this->modelClass)) {
            throw new Exception("Define a model class in order to use NetworkController");
        }

        $this->model = new $this->modelClass;

        $transformerClass = $this->model->getTransformerClass();
        if (empty($transformerClass)) {
            throw new Exception("Model $this->modelClass needs a transformer in order to use NetworkController");
        }
        $this->transformerInstance = new $transformerClass;

        $this->responseHelper = new JsonResponseHelper();

        $this->orderAble           = $this->model->getOrderAble();
        $this->filterAble          = $this->model->getFilterAble();
        $this->filterAbleRelations = $this->model->getFillableRelations();
        $this->resolveAble         = $this->model->getResolveAble();
        foreach ($this->resolveAble as $relation) {
            if (!str_contains($relation, '.') && !method_exists($this->model, $relation)) {
                throw new Exception("Create a `$relation()` method to define relation in $this->modelClass");
            }
        }
        $this->queryAble     = $this->model->getQueryAble();
        $this->aggregateAble = $this->model->getAggregateAble();

        $approvedResolve = [];
        $nestedResolve   = [];
        $resolve         = array_merge($this->defaultResolve, request()->input(self::RESOLVE_PARAM, []));
        if (!empty($resolve)) {
            foreach ($resolve as $resolveValue) {
                if (str_contains($resolveValue, '.')) {
                    if (!in_array(NestedIncludesInterface::class, class_implements($this->transformerInstance) ?: [])) {
                        continue;
                    }
                    $resolveArray = explode('.', $resolveValue);
                    if (!in_array($resolveArray[0], $this->resolveAble, TRUE)) {
                        continue;
                    }

                    $this->model->addToWith($resolveValue);
                    $approvedResolve[] = $resolveArray[0];

                    $nested = [];
                    for ($i = count($resolveArray) - 1; $i >= 0; $i--) {
                        $nested = [$resolveArray[$i] => $nested];
                    }

                    $nestedResolve = array_replace_recursive($nestedResolve, $nested);

                    continue;
                }

                if (in_array($resolveValue, $this->resolveAble, TRUE)) {
                    $approvedResolve[] = $resolveValue;
                    $this->model->addToWith($resolveValue);
                }
            }
        }

        $this->transformerInstance->setDefaultIncludes($approvedResolve);
        $this->transformerInstance->setAvailableIncludes($this->model->getWith());
        if (in_array(NestedIncludesInterface::class, class_implements($this->transformerInstance) ?: [])) {
            $this->transformerInstance->setNestedIncludes($nestedResolve);
        }
    }

    /**
     * If the model and the provided attribute are translatable, the `model_translation` table needs to be joined,
     * along with all translatable columns.
     *
     * @param  BaseModel  $model
     * @param  string  $attributeName
     * @param $builder
     * @return void
     */
    private function joinTranslationModelTableIfNecessary(BaseModel $model, string $attributeName, &$builder): void
    {
        if (!$model->isTranslatable() || !$model->isTranslationAttribute($attributeName)) {
            return;
        }

        $translationTableName = $model->translations()->getRelated()->getTable();

        //check if this table is already joined
        if (!empty($builder->getQuery()->joins)) {
            foreach ($builder->getQuery()->joins as $joinKey => $join) {
                /** @var JoinClause $join */
                if ($join->table === $translationTableName) {
                    //if the model is sluggable and the filtering is for slug, all languages must be used.
                    //therefore, if the translation table has already been joined, remove it and join it again
                    if (
                        !$model->isSlugAble()
                        || $attributeName !== $model->getSlugPropertyName()
                        || empty($join->wheres)
                    ) {
                        return;
                    }
                    unset($builder->getQuery()->joins[$joinKey]);
                    break;
                }
            }
        }

        $selectQueryPart = [$model->getTable() . '.*'];
        foreach ($model->getTranslatedAttributes() as $translatedAttribute) {
            $selectQueryPart[] = $translationTableName . '.' . $translatedAttribute;
        }

        if ($model->isSlugAble() && $attributeName === $model->getSlugPropertyName()) {
            $builder = $builder->leftJoin(
                $translationTableName,
                $model->translations()->getQualifiedForeignKeyName(),
                $model->translations()->getQualifiedParentKeyName()
            )->select($selectQueryPart);

            return;
        }

        $builder = $builder->leftJoin($translationTableName, function ($leftJoin) use ($model) {
            $leftJoin->on(
                $model->translations()->getQualifiedForeignKeyName(),
                $model->translations()->getQualifiedParentKeyName()
            );
            $leftJoin->on(
                'locale',
                DB::raw("'" . app('Astrotomic\Translatable\Locales')->current() . "'")
            );
        })->select($selectQueryPart);
    }

    /**
     * Searches the request for 'limit' parameter;
     * If it's available, overrides the default itemsPerPage property value
     *
     * @param  Request  $request
     * @return int|string
     */
    private function getItemsPerPageFromRequest(Request $request): int|string
    {
        $itemsPerPage = $request->get(self::LIMIT_PARAM);

        if (empty($itemsPerPage)) {
            return $this->itemsPerPage;
        }

        if (is_numeric($itemsPerPage) && (int) $itemsPerPage > 0) {
            return (int) $itemsPerPage;
        }

        $itemsPerPage = strtolower($itemsPerPage);
        if (in_array($itemsPerPage, [self::LIMIT_ALL, self::LIMIT_EMPTY], TRUE)) {
            return $itemsPerPage;
        }

        return $this->itemsPerPage;
    }

    /**
     * Prepares a string for filtering, according to the pre-defined match operators
     *
     * @param  string  $searchWord
     * @param  string  $operator
     * @return string
     */
    protected function getFilterLikeSearchWordValue(string $searchWord, string $operator): string
    {
        return match ($operator) {
            self::FILTER_RIGHT_MATCH => mb_strtolower($searchWord) . '%',
            self::FILTER_LEFT_MATCH => '%' . mb_strtolower($searchWord),
            default => '%' . mb_strtolower($searchWord) . '%',
        };
    }

    /**
     * Applies a filter to the given query builder based on the provided key, value, and operator.
     * Supports nested filtering for related models if the filter key indicates a relationship.
     *
     * @param  mixed  $builder  The query builder instance to which the filter will be applied.
     * @param  string  $filterKey  The key or column name to filter against. Can include relationships (e.g., "relation.key").
     * @param  string|null  $filterValue  The value to filter by. Null values are allowed to indicate no filtering.
     * @param  string  $filterOperator  The operator to use for the filter (default '='). Examples include '=', '<', '>'.
     *
     * @return void
     */
    protected function applyFilter(mixed &$builder, string $filterKey, string|null $filterValue, string $filterOperator = '='): void
    {
        if (str_contains($filterKey, '.')) {
            $filterKey = explode('.', $filterKey);
            if (
                !in_array($filterKey[0], $this->model->getResolveAble(), TRUE)
                || !in_array($filterKey[1], $this->model->{$filterKey[0]}()->getRelated()->getFilterable(), TRUE)
            ) {
                return;
            }
            $builder->whereHas($filterKey[0], function ($innerBuilder) use ($filterKey, $filterValue, $filterOperator) {
                $this->joinTranslationModelTableIfNecessary($this->model->{$filterKey[0]}()->getModel(), $filterKey[1], $innerBuilder);
                $this->applyFilterToBuilder($innerBuilder, $filterKey[1], $filterValue, $filterOperator);
            });

            return;
        }

        if (in_array($filterKey, $this->filterAble, TRUE)) {
            $this->joinTranslationModelTableIfNecessary($this->model, $filterKey, $builder);
            $this->applyFilterToBuilder($builder, $filterKey, $filterValue, $filterOperator);
        }
    }

    /**
     * Applies a relational filter to the query builder by modifying its conditions based on the given relation, filter value, and operator.
     *
     * @param  mixed  $builder  The query builder instance that will be modified.
     * @param  string  $relation  The name of the relation to filter.
     * @param  string  $filterValue  The value used for filtering the results in the specified relation.
     * @param  string  $filterOperator  The operator defining the type of filter to apply (e.g., has or does not have the relation).
     *
     * @return void
     */
    protected function applyRelationFilter(mixed &$builder, string $relation, string $filterValue, string $filterOperator): void
    {
        if (!in_array($relation, $this->filterAbleRelations, TRUE)) {
            return;
        }

        switch ($filterOperator) {
            case self::FILTER_HAS:
                $builder->whereHas($relation, function ($innerBuilder) use ($relation, $filterValue) {
                    $innerBuilder->where($this->model->{$relation}()->getRelated()->getTable() . '.' . $this->model->{$relation}()->getRelated()->getKeyName(), $filterValue);
                });

                return;
            case self::FILTER_DOESNT_HAVE:
                $builder->whereDoesntHave($relation, function ($innerBuilder) use ($relation, $filterValue) {
                    $innerBuilder->where($this->model->{$relation}()->getRelated()->getTable() . '.' . $this->model->{$relation}()->getRelated()->getKeyName(), $filterValue);
                });

                return;
        }
    }

    /**
     * Applies a filter to the query builder based on the provided key, value, and operator.
     *
     * @param $builder
     * @param  string  $filterKey
     * @param  string|null  $filterValue
     * @param  string  $filterOperator
     *
     * @return void
     */
    protected function applyFilterToBuilder(&$builder, string $filterKey, string|null $filterValue, string $filterOperator = '='): void
    {
        if (!is_null($filterValue) && strtolower($filterValue) !== 'null') {
            $builder->where($filterKey, $filterOperator, $filterValue);

            return;
        }
        if ($filterOperator === '=') {
            $builder->whereNull($filterKey);

            return;
        }
        if ($filterOperator === '!=') {
            $builder->whereNotNull($filterKey);
        }
    }

    /**
     * Applies a query filter to the given query builder based on the request parameters.
     * Filters are applied to queryable columns, including handling related models and translatable attributes.
     *
     * @param  mixed  $builder  The query builder instance to modify.
     * @param  Request  $request  The HTTP request containing query parameters.
     *
     * @return void
     */
    protected function applyQueryFromRequest(mixed &$builder, Request $request): void
    {
        if ($request->isNotFilled(self::QUERY_PARAM) || empty($this->queryAble)) {
            return;
        }

        foreach ($this->queryAble as $column => $operators) {
            if (is_numeric($column)) {
                $column = $operators; //if the column is numeric, there is no explicit operator set
            }
            $this->joinTranslationModelTableIfNecessary($this->model, $column, $builder);
        }

        $builder = $builder->where(
            function ($query) use ($request) {
                $queryWord = $request->get(self::QUERY_PARAM);
                foreach ($this->queryAble as $column => $operators) {
                    //if the column is numeric, there is no explicit operator set, use the default %% operator
                    if (is_numeric($column)) {
                        $column    = $operators;
                        $operators = self::FILTER_FULL_MATCH;
                    }
                    if (method_exists($this->model, $column) && in_array($column, $this->model->getWith())) {
                        $relatedModel = $this->model->{$column}()->getRelated();
                        foreach ($relatedModel->getQueryAble() as $relatedQueryableKey => $relatedQueryableOperators) {
                            if ($relatedModel->isTranslatable() && $relatedModel->isTranslationAttribute($relatedQueryableKey)) {
                                $query->orWhereHas(
                                    $column,
                                    function ($q) use ($relatedQueryableKey, $relatedQueryableOperators, $queryWord) {
                                        $q->whereTranslationLike(
                                            $relatedQueryableKey,
                                            $this->getFilterLikeSearchWordValue($queryWord, $relatedQueryableOperators)
                                        );
                                    }
                                );
                                continue;
                            }

                            $query->orWhereHas(
                                $column,
                                function ($q) use ($relatedQueryableKey, $relatedQueryableOperators, $queryWord) {
                                    $q->where(
                                        $relatedQueryableKey,
                                        'LIKE',
                                        $this->getFilterLikeSearchWordValue($queryWord, $relatedQueryableOperators)
                                    );
                                }
                            );
                        }
                    } elseif (!in_array($column, $this->resolveAble)) {
                        $query->orWhere(
                            $column,
                            'LIKE',
                            $this->getFilterLikeSearchWordValue($queryWord, $operators)
                        );
                    }
                }
            });
    }

    /**
     * Applies orderBy clause to the provided builder, by using the provided sort and order by options.
     * Supports related models, one level deep.
     *
     * @param  mixed  $builder
     * @param  Request  $request
     */
    protected function applySortAndOrderFromRequest(mixed &$builder, Request $request): void
    {
        $sort    = $request->get(self::SORT_PARAM);
        $orderBy = $request->get(self::ORDER_BY_PARAM);

        $sort = $sort ? trim(strtolower($sort)) : $this->defaultSort;
        if ($sort !== self::SORT_ASC && $sort !== self::SORT_DESC) {
            $sort = $this->defaultSort;
        }

        $orderBy = $orderBy ? trim(strtolower($orderBy)) : $this->defaultOrder;

        if (in_array($orderBy, $this->orderAble)) {
            if ($this->model->isTranslatable() && $this->model->isTranslationAttribute($orderBy)) {
                $this->joinTranslationModelTableIfNecessary($this->model, $orderBy, $builder);
            }

            $scopeMethodName = 'orderBy' . str_replace('_', '', ucwords($orderBy, '_'));
            if (method_exists($this->model, 'scope' . ucfirst($scopeMethodName))) {
                $builder = $builder->{$scopeMethodName}($sort);

                return;
            }

            $builder = $builder->orderBy($orderBy, $sort);

            return;
        }

        if (str_contains($orderBy, '.')) {
            $orderBy = explode('.', $orderBy);
            if (
                count($orderBy) === 2
                && in_array($orderBy[0], $this->resolveAble)
                && in_array($orderBy[1], $this->model->{$orderBy[0]}()->getRelated()->getOrderAble())
            ) {
                $relation = $this->model->{$orderBy[0]}();

                if (!in_array(get_class($relation), [HasOne::class, BelongsTo::class])) {
                    return;
                }

                $orderByInnerQuery = $relation->getRelated();
                $this->joinTranslationModelTableIfNecessary($relation->getRelated(), $orderBy[1], $orderByInnerQuery);

                $tableName      = $orderByInnerQuery->getModel()->getTable();
                $ownerKeyName   = $relation->getQualifiedOwnerKeyName();
                $foreignKeyName = $relation->getQualifiedForeignKeyName();

                if ($tableName === $this->model->getTable()) {
                    $tableName    .= ' as inner_table';
                    $ownerKeyName = 'inner_table.' . $relation->getOwnerKeyName();
                }

                $orderByInnerQuery->select($orderBy[1])->from($tableName);

                switch (get_class($relation)) {
                    case HasOne::class:
                        $orderByInnerQuery->select($orderBy[1])->whereColumn($foreignKeyName, $ownerKeyName);
                        break;
                    case BelongsTo::class:
                        $orderByInnerQuery->select($orderBy[1])->whereColumn($ownerKeyName, $foreignKeyName);
                        break;
                }

                $builder = $builder->orderBy($orderByInnerQuery, $sort);

                return;
            }
        }

        $builder = $builder->orderBy($this->defaultOrder, $sort);
    }

    /**
     * Applies filters from the request to the query builder.
     * Supports a variety of filter types, including comparison operators,
     * inclusion and exclusion conditions, and relation-based filters.
     *
     * @param  mixed  $builder  The query builder to which the filters will be applied.
     * @param  Request  $request  The current HTTP request containing filter parameters.
     *
     * @return void
     */
    protected function applyFiltersFromRequest(mixed &$builder, Request $request): void
    {
        $filters = array_merge($this->defaultFilters, $request->array(self::FILTERS_PARAM));
        if (empty($filters)) {
            return;
        }

        foreach ($filters as $filterKey => $filterValue) {
            //ignore filters that have a deeper-than-one relation defined
            if (substr_count($filterKey, '.') > 1) {
                continue;
            }

            if (!is_array($filterValue)) {
                $this->applyFilter($builder, $filterKey, $filterValue);
                continue;
            }

            foreach ($filterValue as $requestOperator => $value) {
                switch ($requestOperator) {
                    case self::FILTER_NOT:
                        $this->applyFilter($builder, $filterKey, $value, '!=');
                        break;
                    case self::FILTER_GREATER_THAN:
                        $this->applyFilter($builder, $filterKey, $value, '>');
                        break;
                    case self::FILTER_LESSER_THAN:
                        $this->applyFilter($builder, $filterKey, $value, '<');
                        break;
                    case self::FILTER_GREATER_THAN_OR_EQUALS:
                        $this->applyFilter($builder, $filterKey, $value, '>=');
                        break;
                    case self::FILTER_LESSER_THAN_OR_EQUALS:
                        $this->applyFilter($builder, $filterKey, $value, '<=');
                        break;
                    case self::FILTER_FULL_MATCH:
                    case self::FILTER_RIGHT_MATCH:
                    case self::FILTER_LEFT_MATCH:
                        $this->applyFilter($builder, $filterKey, $this->getFilterLikeSearchWordValue($value, $requestOperator), 'LIKE');
                        break;
                    case self::FILTER_IN:
                        if ($filterKey === 'id') {
                            $filterKey = $this->model->getQualifiedKeyName();
                        }
                        $builder->whereIn($filterKey, explode(',', $value));
                        break;
                    case self::FILTER_NOT_IN:
                        if ($filterKey === 'id') {
                            $filterKey = $this->model->getQualifiedKeyName();
                        }
                        $builder->whereNotIn($filterKey, explode(',', $value));
                        break;
                    case self::FILTER_HAS:
                    case self::FILTER_DOESNT_HAVE:
                        $this->applyRelationFilter($builder, $filterKey, $value, $requestOperator);
                        break;
                    default:
                        $this->applyFilter($builder, $filterKey, $value);
                        break;
                }
            }
        }
    }

    /**
     * Populates the meta-property of the fractal resource
     *
     * @param  ResourceAbstract  $resource
     * @return void
     */
    protected function setFractalResourceMetaValue(ResourceAbstract $resource): void
    {
        $resource->setMetaValue(
            self::META_ROUTE_INFO_PARAM,
            [
                self::ORDER_BY_PARAM  => !empty($this->orderAble) ? $this->orderAble : [],
                self::SORT_PARAM      => [self::SORT_ASC, self::SORT_DESC],
                self::LIMIT_PARAM     => [self::LIMIT_ALL, self::LIMIT_EMPTY],
                self::FILTERS_PARAM   => !empty($this->filterAble) ? $this->filterAble : [],
                self::QUERY_PARAM     => !empty($this->queryAble) ? $this->queryAble : [],
                self::RESOLVE_PARAM   => !empty($this->resolveAble) ? $this->resolveAble : [],
                self::AGGREGATE_PARAM => !empty($this->aggregateAble) ? $this->aggregateAble : [],
                self::SLUG_PARAM      => $this->model->isSlugAble() ? $this->model->getSlugPropertyName() : FALSE,
            ]
        );
    }

    /**
     * Initialize the builder for the 'index' function. Allows custom logic.
     *
     * @param  Request  $request
     * @return Builder|BaseModel
     */
    protected function getIndexQueryBuilder(Request $request): Builder|BaseModel
    {
        return $this->model;
    }

    /**
     * Returns a collection of items from the current model after a GET request
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        $builder = $this->getIndexQueryBuilder($request);

        $this->applyQueryFromRequest($builder, $request);
        $this->applySortAndOrderFromRequest($builder, $request);
        $this->applyFiltersFromRequest($builder, $request);

        if ($request->get(self::WITH_DELETED) && $this->model->hasSoftDeletes()) {
            $builder = $builder->withTrashed();
        } elseif ($request->get(self::ONLY_DELETED) && $this->model->hasSoftDeletes()) {
            $builder = $builder->onlyTrashed();
        }

        $itemsPerPage = $this->getItemsPerPageFromRequest($request);

        switch ($itemsPerPage) {
            case self::LIMIT_EMPTY:
                $fractalCollection = new Collection([], $this->transformerInstance);
                break;
            case self::LIMIT_ALL:
                $fractalCollection = new Collection($builder->get(), $this->transformerInstance);
                break;
            default:
                $paginator         = $builder->paginate($itemsPerPage);
                $fractalCollection = new Collection($paginator->getCollection(), $this->transformerInstance);
                $fractalCollection->setPaginator(new IlluminatePaginatorAdapter($paginator));
                break;
        }

        if ($request->get(self::AGGREGATE_PARAM)) {
            $aggregations = [];
            foreach ($request->get(self::AGGREGATE_PARAM) as $aggregation) {
                if (
                    !in_array($aggregation, $this->aggregateAble)
                    || !method_exists($this, self::AGGREGATE_PARAM . '_' . $aggregation)
                ) {
                    continue;
                }
                $aggregations[$aggregation] = $this->{self::AGGREGATE_PARAM . '_' . $aggregation}($builder);
            }
            if (!empty($aggregations)) {
                $fractalCollection->setMetaValue(self::AGGREGATE_PARAM, $aggregations);
            }
        }

        if ($request->filled(self::SHOW_META_PARAM)) {
            $this->setFractalResourceMetaValue($fractalCollection);
        }

        return $this->responseHelper->fractalResourceToJsonResponse($fractalCollection);
    }

    /**
     * Select an entry from the database, using the provided id or slug (if the model is slug-able)
     *
     * @param  int|string  $id
     * @return Model
     */
    protected function getByIdOrSlug(int|string $id): Model
    {
        if (!$this->model->isSlugAble() || is_numeric($id)) {
            return $this->model->findOrFail($id);
        }
        if ($this->model->isTranslatable()) {
            return $this->model::whereTranslation($this->model->getSlugPropertyName(), $id)->firstOrFail();
        }

        return $this->model::where($this->model->getSlugPropertyName(), $id)->firstOrFail();
    }

    /**
     * Returns a single item from the current model after a GET request
     *
     * @param  Request  $request
     * @param  int|string  $id
     * @return JsonResponse
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        try {
            $fractalItem = new Item($this->getByIdOrSlug($id), $this->transformerInstance);

            if ($request->filled(self::SHOW_META_PARAM)) {
                $this->setFractalResourceMetaValue($fractalItem);
            }

            return $this->responseHelper->fractalResourceToJsonResponse($fractalItem);
        } catch (ModelNotFoundException) {
            return $this->responseHelper->errorNotFoundJsonResponse();
        }
    }

    /**
     * Retrieves the translations for the given model by ID or slug.
     *
     * @param  int|string  $id
     *
     * @return JsonResponse
     */
    public function translations(int|string $id): JsonResponse
    {
        $response = [];
        if (!$this->model->isTranslatable() || empty($this->model->getTranslatedAttributes())) {
            return $this->responseHelper->standardDataResponse($response);
        }

        $object = $this->getByIdOrSlug($id);
        foreach ($this->model->getTranslatedAttributes() as $translatedAttribute) {
            foreach (config('translatable.locales') as $locale) {
                $response[$translatedAttribute][$locale] = $object->getTranslation($locale)->{$translatedAttribute} ?? NULL;
            }
        }

        return $this->responseHelper->standardDataResponse($response);
    }

    /**
     * Attempt to recover a deleted object, based on the current request
     *
     * @param  Request  $request
     * @return Model|null
     */
    private function recoverDeletedObject(Request $request): ?Model
    {
        $deletedObject = $this->model->onlyTrashed();
        foreach ($this->model->getFillable() as $fillable) {
            if (!$request->filled($fillable)) {
                continue;
            }

            $deletedObject->where($fillable, '=', $request->input($fillable));
        }

        $deletedObject = $deletedObject->first();
        if ($deletedObject !== NULL) {
            $deletedObject->restore();

            return $deletedObject;
        }

        return NULL;
    }

    /**
     * Check if resolved objects are provided in Store or Update requests and use the defined relations
     * to set the foreign key values of the current model
     *
     * @param  Request  $request
     */
    protected function handleStoreUpdateResolvedRelations(Request $request): void
    {
        if ($this->withoutRelationInsert) {
            return;
        }

        foreach ($this->model->getWith() as $relation) {
            if (!$request->has($relation)) {
                continue;
            }

            $relationRequest = $request->input($relation);
            if (count($relationRequest) === 1 && array_key_exists(BaseModel::DATA, $relationRequest)) {
                $relationRequest = $relationRequest[BaseModel::DATA];
            }

            switch (get_class($this->model->{$relation}())) {
                case HasOne::class:
                    if (!array_key_exists($this->model->{$relation}()->getForeignKeyName(), $relationRequest)) {
                        continue 2;
                    }

                    $this->model->{$this->model->{$relation}()->getLocalKeyName()} =
                        $relationRequest[$this->model->{$relation}()->getForeignKeyName()];
                    break;
                case BelongsTo::class:
                    if (!array_key_exists($this->model->{$relation}()->getOwnerKeyName(), $relationRequest)) {
                        continue 2;
                    }

                    $this->model->{$this->model->{$relation}()->getForeignKeyName()} =
                        $relationRequest[$this->model->{$relation}()->getOwnerKeyName()];
                    break;
            }
        }
    }

    /**
     * Inserts an item from the current model after a POST request
     * Will attempt to recover deleted items
     *
     * @param  Request  $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isValidated) {
            $this->validateInput($request);
        }

        if ($this->model->hasSoftDeletes()) {
            $recoveredObject = $this->recoverDeletedObject($request);
            if (!empty($recoveredObject)) {
                $recoveredObject->loadMissing($this->model->getWith());

                return $this->responseHelper->fractalResourceToJsonResponse(
                    new Item($recoveredObject, $this->transformerInstance)
                );
            }
        }

        foreach ($this->model->getFillable() as $fillAble) {
            if ($request->has($fillAble)) {
                $this->model->$fillAble = $request->input($fillAble);
            }
        }

        foreach ($this->model->getTranslatedAttributes() as $translatedAttribute) {
            if ($request->has($translatedAttribute)) {
                foreach ($request->input($translatedAttribute) as $locale => $value) {
                    $this->model->{$translatedAttribute . ':' . $locale} = $value;
                }
            }
        }

        $this->handleStoreUpdateResolvedRelations($request);

        $this->model->save();
        $this->model->loadMissing($this->model->getWith());

        return $this->responseHelper->fractalResourceToJsonResponse(new Item($this->model, $this->transformerInstance));
    }

    /**
     * Updates an item from the current model after a PUT/PATCH request
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->isValidated) {
            $this->validateInput($request, $id);
        }

        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $exception) {
            return $this->responseHelper->errorNotFoundJsonResponse();
        }

        foreach ($this->model->getFillable() as $fillAble) {
            if ($request->has($fillAble)) {
                $this->model->$fillAble = $request->input($fillAble);
            }
        }

        foreach ($this->model->getTranslatedAttributes() as $translatedAttribute) {
            if ($request->has($translatedAttribute)) {
                foreach ($request->input($translatedAttribute) as $locale => $value) {
                    $this->model->{$translatedAttribute . ':' . $locale} = $value;
                }
            }
        }

        $this->handleStoreUpdateResolvedRelations($request);

        $this->model->save();

        // Refresh model data to populate all required values
        $this->model->refresh();

        return $this->responseHelper->fractalResourceToJsonResponse(new Item($this->model, $this->transformerInstance));
    }

    /**
     * Deletes an item from the current model after a DELETE request
     *
     * @param  int|string  $id
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(int|string $id): JsonResponse
    {
        try {
            $this->getByIdOrSlug($id)->delete();

            return $this->responseHelper->noContentResponse();
        } catch (ModelNotFoundException $exception) {
            return $this->responseHelper->errorNotFoundJsonResponse();
        }
    }

    /**
     * Validates the input when inserting/updating.
     * All models should override this method
     *
     * @param  Request  $request
     * @param  int|null  $id
     */
    protected abstract function validateInput(Request $request, int $id = NULL);
}
