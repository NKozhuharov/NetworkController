<?php

namespace Nevestul4o\NetworkController;

use BadMethodCallException;
use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\Models\BaseModel;

abstract class NetworkController extends BaseController
{
    use Helpers;
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The location of all models
     */
    const MODELS_DIR = '\App\Http\Models\\';

    const ORDER_BY_PARAM = 'orderby';
    const SORT_PARAM = 'sort';
    const LIMIT_PARAM = 'limit';
    const QUERY_PARAM = 'query';
    const FILTERS_PARAM = 'filters';
    const RESOLVE_PARAM = 'resolve';
    const SHOW_META_PARAM = 'showMeta';
    const AGGREGATE_PARAM = 'aggregate';
    const META_ROUTE_INFO_PARAM = 'route_info';

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
     * The name of the model
     *
     * @var string
     */
    protected $modelName;

    /**
     * The full path to the model
     *
     * @var BaseModel
     */
    protected $model;

    /**
     * If we are using a transformer apply the API transformer casing
     *
     * @var TransformerAbstract
     */
    protected $transformer;

    /**
     * If we are using a transformer, create an instance here
     *
     * @var TransformerAbstract
     */
    protected $transformerInstance;

    /**
     * How many items to show per page
     *
     * @var int
     */
    protected $itemsPerPage = 20;

    /**
     * Ignore second validation when using parent:: method
     *
     * @var bool
     */
    protected $isValidated = FALSE;

    /**
     * Ignore additional relation setter
     *
     * @var bool
     */
    protected $withoutRelationInsert = FALSE;

    /**
     * The default column to order collections by
     *
     * @var string
     */
    protected $defaultOrder = BaseModel::F_ID;

    /**
     * The default direction to sort collections by
     *
     * @var string
     */
    protected $defaultSort = self::SORT_DESC;

    /**
     * Contains all columns, which can be used to order a collection
     *
     * @var array
     */
    protected $orderAble;

    /**
     * Contains all columns, which can be used to filter a collection
     *
     * @var array
     */
    protected $filterAble;

    /**
     * Contains all related models, which can be resolved, when obtaining a collection
     *
     * @var array
     */
    protected $resolveAble;

    /**
     * Contains all columns, which can be used to query a collection
     *
     * @var array
     */
    protected $queryAble;

    /**
     * Contains the function names, which can be used to aggregate a collection
     *
     * @var array
     */
    protected $aggregateAble;

    /**
     * Create a new controller instance.
     * @throws Exception
     */
    public function __construct()
    {
        if (!empty($this->modelName)) {
            $this->modelName = self::MODELS_DIR . $this->modelName;
            $this->model = new $this->modelName;

            $this->transformer = $this->model->getTransformer();
            if (empty($this->transformer)) {
                throw new Exception("Model {$this->modelName} needs a transformer in order to use NetworkController");
            }
            $this->transformerInstance = new $this->transformer;

            $this->orderAble = $this->model->getOrderAble();
            $this->filterAble = $this->model->getFilterAble();
            $this->resolveAble = $this->model->getResolveAble();
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
    private function getFilterLikeSearchWordValue(string $searchWord, string $operator): string
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
    private function applyFilter(&$builder, string $filterKey, string $filterValue, string $filterOperator = '='): void
    {
        if (strstr($filterKey, '.')) {
            $filterKey = explode('.', $filterKey);
            if (
                !in_array($filterKey[0], $this->model->getWith(), TRUE)
                || !in_array($filterKey[1], $this->model->{$filterKey[0]}()->getRelated()->getFilterable(), TRUE)
            ) {
                return;
            }
            $builder->whereHas($filterKey[0], function ($q) use ($filterKey, $filterValue, $filterOperator) {
                $q->where($filterKey[1], $filterOperator, $filterValue);
            });
            return;
        }

        if (in_array($filterKey, $this->filterAble, TRUE)) {
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
            $builder = $builder->orderBy($orderBy, $sort);
            return;
        }

        if (strstr($orderBy, '.')) {
            $orderBy = explode('.', $orderBy);
            if (
                count($orderBy) === 2
                && in_array($orderBy[0], $this->resolveAble)
                && in_array($orderBy[1], $this->model->{$orderBy[0]}()->getRelated()->getOrderAble())
            ) {
                switch (get_class($this->model->client())) {
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
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request): Response
    {
        $builder = $this->model;
        if ($request->filled(self::QUERY_PARAM) && !empty($this->queryAble) && is_array($this->queryAble)) {
            $builder = $builder->where(
                function ($query) use ($request) {
                    $queryWord = $request->get(self::QUERY_PARAM);
                    foreach ($this->queryAble as $column => $operators) {
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
            if (!is_array($this->filterAble)) {
                $this->filterAble = [$this->filterAble];
            }
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

        switch ($this->itemsPerPage) {
            case self::LIMIT_EMPTY:
                $response = $this->response->collection($builder->take(0)->get(), $this->transformerInstance);
                break;
            case self::LIMIT_ALL:
                $response = $this->response->collection($builder->get(), $this->transformerInstance);
                break;
            default:
                $response = $this->response->paginator($builder->paginate($this->itemsPerPage), $this->transformerInstance);
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
                $response->addMeta(self::AGGREGATE_PARAM, $aggregations);
            }
        }

        if (($request->filled(self::SHOW_META_PARAM))) {
            $response->addMeta(
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

        return $response;
    }

    /**
     * Returns a single item from the current model after a GET request
     *
     * @param int $id
     * @return Response
     */
    public function show(int $id = 0): Response
    {
        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->response->errorNotFound();
        }
        return $this->response->item($this->model, $this->transformerInstance);
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

            switch (get_class($this->model->client())) {
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
     * @return mixed
     */
    public function store(Request $request): Response
    {
        if (!$this->isValidated) {
            $this->validateInput($request);
        }

        $recoveredObject = $this->recoverDeletedObject($request);
        if (!empty($recoveredObject)) {
            $recoveredObject->loadMissing($this->model->getWith());
            return $this->response->item($recoveredObject, $this->transformerInstance);
        }

        foreach ($this->model->getFillable() as $fillAble) {
            if ($request->has($fillAble)) {
                $this->model->$fillAble = $request->input($fillAble);
            }
        }

        $this->handleStoreUpdateResolvedRelations($request);

        $this->model->save();
        $this->model->loadMissing($this->model->getWith());

        return $this->response->item($this->model, $this->transformerInstance);
    }

    /**
     * Updates an item from the current model after a PUT/PATCH request
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, int $id): Response
    {
        if (!$this->isValidated) {
            $this->validateInput($request, $id);
        }

        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->response->errorNotFound();
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

        return $this->response->item($this->model, $this->transformerInstance);
    }

    /**
     * Deletes an item from the current model after a DELETE request
     *
     * @param int $id
     *
     * @return Response
     * @throws Exception
     */
    public function destroy(int $id): Response
    {
        $this->model = $this->model->findOrFail($id);

        $this->model->delete();

        return $this->response->noContent();
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
