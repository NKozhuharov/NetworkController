<?php

namespace Nevestul4o\NetworkController;

use Dingo\Api\Http\Response;
use Dingo\Api\Routing\Helpers;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
                        } catch (\BadMethodCallException $exception) {
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

        $orderBy = $this->defaultOrder;
        $sort = $this->defaultSort;

        if (
            $request->filled(self::ORDER_BY_PARAM)
            && in_array($request->get(self::ORDER_BY_PARAM), $this->orderAble)
        ) {
            $orderBy = $request->get(self::ORDER_BY_PARAM);
        }

        if ($request->filled(self::SORT_PARAM)) {
            $sort = strtolower($request->get(self::SORT_PARAM));
            if ($sort !== self::SORT_ASC && $sort !== self::SORT_DESC) {
                $sort = $this->defaultSort;
            }
        }
        $builder = $builder->orderBy($orderBy, $sort);
        unset($orderBy, $sort);

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
                            default:
                                $this->applyFilter($builder, $filterKey, $value);
                                break;
                        }
                    }
                } else {
                    $this->applyFilter($builder, $filterKey, $filterValue, '=');
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
        };

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
                    self::SORT_PARAM      => [selF::SORT_ASC, self::SORT_DESC],
                    self::LIMIT_PARAM     => [selF::LIMIT_ALL, self::LIMIT_EMPTY],
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
            return $this->response->item($this->model->findOrFail($id), $this->transformerInstance);
        } catch (ModelNotFoundException $e) {
            return $this->response->errorNotFound();
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

        $deletedObject = $this->model->onlyTrashed();
        foreach ($this->model->getFillable() as $fillable) {
            if (!$request->filled($fillable)) {
                continue;
            }

            $deletedObject->where($fillable, '=', $request->input($fillable));
        }
        $deletedObject->get();
        if ($deletedObject->count() > 0) {
            $this->model = $deletedObject;
            $this->model->restore();
        } else {
            foreach ($this->model->getFillable() as $fillable) {
                $this->model->$fillable = $request->filled($fillable)
                    ? $request->input($fillable)
                    : NULL;
            }

            if (!$this->withoutRelationInsert) {
                foreach ($this->model->getWith() as $relation) {
                    $relationId = "{$relation}_id";
                    $relationRequest = $request->input($relation);
                    $this->model->$relationId = $relationRequest['data']['id'] ?? NULL;
                }
            }

            $this->model->save();
        }

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
            return $this->response->errorNotFound();
        }

        $fillables = $this->model->getFillable();

        foreach ($fillables as $fillable) {
            if ($request->$fillable === NULL || $request->has($fillable)) {
                $this->model->$fillable = $request->input($fillable);
            }
        }

        if (!$this->withoutRelationInsert) {
            foreach ($this->model->getWith() as $relation) {
                foreach ($request->request as $key => $value) {
                    if ($key === $relation) {
                        $relationRequest = $request->input($relation);
                        if (isset($relationRequest['data']['id'])) {
                            $this->model->{"{$relation}_id"} = $relationRequest['data']['id'];
                        } elseif ($value === NULL) {
                            $this->model->{"{$relation}_id"} = NULL;
                        }
                        break;
                    }
                }
            }
        }

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
