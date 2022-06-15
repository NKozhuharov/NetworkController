<?php

namespace Nevestul4o\NetworkController;

use BadMethodCallException;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\Models\BaseModel;

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
    const META_ROUTE_INFO_PARAM = 'route_info';

    const ONLY_DELETED = 'only_deleted';
    const WITH_DELETED = 'with_deleted';

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';
    const LIMIT_ALL = 'all';
    const LIMIT_EMPTY = 'empty';

    const FILTER_GREATER_THAN = 'gt';
    const FILTER_LESSER_THAN = 'lt';
    const FILTER_GREATER_THAN_OR_EQUALS = 'gte';
    const FILTER_LESSER_THAN_OR_EQUALS = 'lte';
    const FILTER_FULL_MATCH = '%%';
    const FILTER_RIGHT_MATCH = '*%';
    const FILTER_LEFT_MATCH = '%*';
    const FILTER_IN = 'in';

    /**
     * The class of the model
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * An instance of the model
     *
     * @var BaseModel
     */
    protected BaseModel $model;

    /**
     * If we are using a transformer, create an instance here
     *
     * @var TransformerAbstract
     */
    protected TransformerAbstract $transformerInstance;

    /**
     * How many items to show per page
     *
     * @var int|string
     */
    protected int|string $itemsPerPage = 20;

    /**
     * Ignore second validation when using parent:: method
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
     * Contains all related models, which can be resolved, when obtaining a collection
     *
     * @var array
     */
    protected array $resolveAble;

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
            throw new Exception("Model {$this->modelClass} needs a transformer in order to use NetworkController");
        }
        $this->transformerInstance = new $transformerClass;

        $this->responseHelper = new JsonResponseHelper();

        $this->orderAble = $this->model->getOrderAble();
        $this->filterAble = $this->model->getFilterAble();
        $this->resolveAble = $this->model->getResolveAble();
        foreach ($this->resolveAble as $relation) {
            if (!method_exists($this->model, $relation)) {
                throw new Exception("Create a `$relation()` method to define relation in {$this->modelClass}");
            }
        }
        $this->queryAble = $this->model->getQueryAble();
        $this->aggregateAble = $this->model->getAggregateAble();

        $this->getItemsPerPageFromGet();

        $approvedResolve = [];
        $resolve = request()->input(self::RESOLVE_PARAM);
        if (is_array($resolve) && !empty($resolve)) {
            foreach ($resolve as $resolveValue) {
                if (in_array($resolveValue, $this->resolveAble, TRUE)) {
                    try {
                        $this->model::has($resolveValue);
                        $approvedResolve[] = $resolveValue;
                        if (!in_array($resolveValue, $this->model->getWith())) {
                            $this->model->addToWith(str_replace('-', '.', $resolveValue));
                        }
                    } catch (BadMethodCallException $exception) {
                    }
                }
            }
        }

        $this->transformerInstance->setDefaultIncludes($approvedResolve);
        $this->transformerInstance->setAvailableIncludes($this->model->getWith());
    }

    /**
     * If the model and the provided attribute are translatable, the `model_translation` table needs to be joined,
     * along with all translatable columns.
     *
     * @param  string  $attributeName
     * @param $builder
     * @return void
     */
    private function joinTranslationModelTableIfNecessary(BaseModel $model, string $attributeName, &$builder): void
    {
        if (
            !$model->isTranslatable() ||
            !$model->isTranslationAttribute($attributeName)
        ) {
            return;
        }

        //check if this table is already joined
        $joins = $builder->getQuery()->joins;
        if (!empty($joins)) {
            foreach ($builder->getQuery()->joins as $join) {
                if ($join->table === $model->translations()->getRelated()->getTable()) {
                    return;
                }
            }
        }

        $selectQueryPart = [$model->getTable().'.*'];
        foreach ($model->getTranslatedAttributes() as $translatedAttribute) {
            $selectQueryPart[] = $model->translations()->getRelated()->getTable().'.'.$translatedAttribute;
        }

        $builder = $builder->leftJoin(
            $model->translations()->getRelated()->getTable(),
            $model->translations()->getQualifiedForeignKeyName(),
            $model->translations()->getQualifiedParentKeyName()
        )
            ->where('locale', app('Astrotomic\Translatable\Locales')->current())
            ->select($selectQueryPart);
    }

    /**
     * Searches the request for 'limit' parameter;
     * If it's available, overrides the default itemsPerPage property value
     *
     * @return void
     */
    private function getItemsPerPageFromGet(): void
    {
        if (isset(request()->{self::LIMIT_PARAM}) && !empty(request()->{self::LIMIT_PARAM})) {
            if (is_numeric(request()->{self::LIMIT_PARAM}) && (int)request()->{self::LIMIT_PARAM} > 0) {
                $this->itemsPerPage = (int)request()->{self::LIMIT_PARAM};
            } elseif (strtolower(request()->{self::LIMIT_PARAM}) === self::LIMIT_ALL) {
                $this->itemsPerPage = self::LIMIT_ALL;
            } elseif (strtolower(request()->{self::LIMIT_PARAM}) === self::LIMIT_EMPTY) {
                $this->itemsPerPage = self::LIMIT_EMPTY;
            }
        }
    }

    /**
     * Prepares a string for filtering, according to the pre-defined match operators
     *
     * @param string $searchWord
     * @param string $operator
     * @return string
     */
    protected function getFilterLikeSearchWordValue(string $searchWord, string $operator): string
    {
        switch ($operator) {
            case self::FILTER_RIGHT_MATCH:
                return mb_strtolower($searchWord) . '%';
            case self::FILTER_LEFT_MATCH:
                return '%' . mb_strtolower($searchWord);
        }
        return '%' . mb_strtolower($searchWord) . '%';
    }

    /**
     * Applies a filter to the $builder query builder.
     * Supports related models, one level deep.
     *
     * @param $builder
     * @param string $filterKey
     * @param string $filterValue
     * @param string $filterOperator
     */
    protected function applyFilter(&$builder, string $filterKey, string $filterValue, string $filterOperator = '='): void
    {
        if (str_contains($filterKey, '.')) {
            $filterKey = explode('.', $filterKey);
            if (
                !in_array($filterKey[0], $this->model->getWith(), TRUE)
                || !in_array($filterKey[1], $this->model->{$filterKey[0]}()->getRelated()->getFilterable(), TRUE)
            ) {
                return;
            }
            $builder->whereHas($filterKey[0], function ($innerBuilder) use ($filterKey, $filterValue, $filterOperator) {
                $this->joinTranslationModelTableIfNecessary($this->model->{$filterKey[0]}()->getModel(), $filterKey[1], $innerBuilder);
                $innerBuilder->where($filterKey[1], $filterOperator, $filterValue);
            });
            return;
        }

        if (in_array($filterKey, $this->filterAble, TRUE)) {
            $this->joinTranslationModelTableIfNecessary($this->model, $filterKey, $builder);
            $builder->where($filterKey, $filterOperator, $filterValue);
        }
    }

    /**
     * Applies orderBy clause to the provided builder, by using the provided sort and order by options.
     * Supports related models, one level deep.
     *
     * @param $builder
     * @param string|null $sort
     * @param string|null $orderBy
     */
    protected function applySortAndOrder(&$builder, ?string $sort, ?string $orderBy)
    {
        $sort = $sort ? trim(strtolower($sort)) : $this->defaultSort;
        if ($sort !== self::SORT_ASC && $sort !== self::SORT_DESC) {
            $sort = $this->defaultSort;
        }

        $orderBy = $orderBy ? trim(strtolower($orderBy)) : $this->defaultOrder;

        if (in_array($orderBy, $this->orderAble)) {
            if ($this->model->isTranslatable() && $this->model->isTranslationAttribute($orderBy)) {
                $this->joinTranslationModelTableIfNecessary($this->model, $orderBy, $builder);
            }

            $builder = $builder->orderBy($orderBy, $sort);
            return;
        }

        //@todo - related models with translations
        if (str_contains($orderBy, '.')) {
            $orderBy = explode('.', $orderBy);
            if (
                count($orderBy) === 2
                && in_array($orderBy[0], $this->resolveAble)
                && in_array($orderBy[1], $this->model->{$orderBy[0]}()->getRelated()->getOrderAble())
            ) {
                switch (get_class($this->model->{$orderBy[0]}())) {
                    case HasOne::class:
                        $builder = $builder->orderBy(
                            $this->model->{$orderBy[0]}()->getRelated()::select($orderBy[1])
                                ->whereColumn(
                                    $this->model->{$orderBy[0]}()->getQualifiedParentKeyName(),
                                    $this->model->{$orderBy[0]}()->getQualifiedForeignKeyName()
                                ),
                            $sort
                        );
                        return;
                    case BelongsTo::class:
                        $builder = $builder->orderBy(
                            $this->model->{$orderBy[0]}()->getRelated()::select($orderBy[1])
                                ->whereColumn(
                                    $this->model->{$orderBy[0]}()->getQualifiedForeignKeyName(),
                                    $this->model->{$orderBy[0]}()->getQualifiedOwnerKeyName()
                                ),
                            $sort
                        );
                        return;
                }
            }
        }

        $builder = $builder->orderBy($this->defaultOrder, $sort);
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
        $builder = $this->model;
        if ($request->filled(self::QUERY_PARAM) && !empty($this->queryAble)) {
            foreach ($this->queryAble as $column => $operators) {
                if (is_numeric($column)) {
                    $column = $operators; //if the column is numeric, there is no explicit operator set
                }
                $this->joinTranslationModelTableIfNecessary($this->model, $column, $builder);
            }
            //@todo - related models with translations

            $builder = $builder->where(
                function ($query) use ($request) {
                    $queryWord = $request->get(self::QUERY_PARAM);
                    foreach ($this->queryAble as $column => $operators) {
                        //if the column is numeric, there is no explicit operator set, use the default %% operator
                        if (is_numeric($column)) {
                            $column = $operators;
                            $operators = self::FILTER_FULL_MATCH;
                        }
                        if (method_exists($this->model, $column) && in_array($column, $this->model->getWith())) {
                            foreach ($this->model->{$column}()->getRelated()->getQueryAble() as $relatedQueryableKey => $relatedQueryableOperators) {
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

        $this->applySortAndOrder($builder, $request->get(self::SORT_PARAM), $request->get(self::ORDER_BY_PARAM));

        $filters = $request->input(self::FILTERS_PARAM);
        if (is_array($filters) && !empty($filters)) {
            foreach ($filters as $filterKey => $filterValue) {
                if (substr_count($filterKey, '.') > 1) {
                    continue;
                }

                if (is_array($filterValue)) {
                    foreach ($filterValue as $requestOperator => $value) {
                        switch ($requestOperator) {
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
                                $builder->whereIn($filterKey, explode(',', $value));
                                break;
                            default:
                                $this->applyFilter($builder, $filterKey, $value);
                                break;
                        }
                    }
                } else {
                    $this->applyFilter($builder, $filterKey, $filterValue);
                }
            }
        }
        unset($filters);

        if ($request->get(self::WITH_DELETED) && $this->model->isSoftDeletable()) {
            $builder = $builder->withTrashed();
        } elseif ($request->get(self::ONLY_DELETED) && $this->model->isSoftDeletable()) {
            $builder = $builder->onlyTrashed();
        }

        switch ($this->itemsPerPage) {
            case self::LIMIT_EMPTY:
                $fractalCollection = new Collection([], $this->transformerInstance);
                break;
            case self::LIMIT_ALL:
                $fractalCollection = new Collection($builder->get(), $this->transformerInstance);
                break;
            default:
                $paginator = $builder->paginate($this->itemsPerPage);
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

        if (($request->filled(self::SHOW_META_PARAM))) {
            $fractalCollection->setMetaValue(
                self::META_ROUTE_INFO_PARAM,
                [
                    self::ORDER_BY_PARAM  => !empty($this->orderAble) ? $this->orderAble : [],
                    self::SORT_PARAM      => [self::SORT_ASC, self::SORT_DESC],
                    self::LIMIT_PARAM     => [self::LIMIT_ALL, self::LIMIT_EMPTY],
                    self::FILTERS_PARAM   => !empty($this->filterAble) ? $this->filterAble : [],
                    self::QUERY_PARAM     => !empty($this->queryAble) ? $this->queryAble : [],
                    self::RESOLVE_PARAM   => !empty($this->resolveAble) ? $this->resolveAble : [],
                    self::AGGREGATE_PARAM => !empty($this->aggregateAble) ? $this->aggregateAble : [],
                ]
            );
        }

        return $this->responseHelper->fractalResourceToJsonResponse($fractalCollection);
    }

    /**
     * Returns a single item from the current model after a GET request
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            if (!$this->model->isSlugAble() || is_numeric($id)) {
                return $this->responseHelper->fractalResourceToJsonResponse(
                    new Item($this->model->findOrFail($id), $this->transformerInstance)
                );
            }

            if ($this->model->isTranslatable()) {
                return $this->responseHelper->fractalResourceToJsonResponse(
                    new Item(
                        $this->model::whereTranslation($this->model->getSlugPropertyName(), $id)->firstOrFail(),
                        $this->transformerInstance
                    )
                );
            }

            return $this->responseHelper->fractalResourceToJsonResponse(
                new Item(
                    $this->model::where($this->model->getSlugPropertyName(), $id)->firstOrFail(),
                    $this->transformerInstance
                )
            );
        } catch (ModelNotFoundException $e) {
            return $this->responseHelper->errorNotFoundJsonResponse();
        }
    }

    /**
     * Attempt to recover a deleted object, based on the current request
     *
     * @param Request $request
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
     * @param Request $request
     */
    private function handleStoreUpdateResolvedRelations(Request $request): void
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
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->isValidated) {
            $this->validateInput($request);
        }

        if ($this->model->isSoftDeletable()) {
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

        $this->handleStoreUpdateResolvedRelations($request);

        $this->model->save();
        $this->model->loadMissing($this->model->getWith());

        return $this->responseHelper->fractalResourceToJsonResponse(new Item($this->model, $this->transformerInstance));
    }

    /**
     * Updates an item from the current model after a PUT/PATCH request
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (!$this->isValidated) {
            $this->validateInput($request, $id);
        }

        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return $this->responseHelper->errorNotFoundJsonResponse();
        }

        foreach ($this->model->getFillable() as $fillAble) {
            if ($request->has($fillAble)) {
                $this->model->$fillAble = $request->input($fillAble);
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
     * @param int $id
     *
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(int $id): JsonResponse
    {
        $this->model = $this->model->findOrFail($id);

        $this->model->delete();

        return $this->responseHelper->noContentResponse();
    }

    /**
     * Validates the input when inserting/updating.
     * All models should override this method
     *
     * @param Request $request
     * @param int|null $id
     */
    protected abstract function validateInput(Request $request, int $id = NULL);
}
