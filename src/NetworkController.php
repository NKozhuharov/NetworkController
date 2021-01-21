<?php

namespace Nevestul4o\NetworkController;

use App\Models\_Model as Model;
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

abstract class NetworkController extends BaseController
{
    use Helpers;
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * The location of all models
     */
    const MODELS_DIR = '\App\Models\\';

    private const LIMIT_ALL = 'all';

    private const ORDER_BY_PARAM = 'orderby';
    private const SORT_PARAM = 'sort';
    private const QUERY_PARAM = 'query';
    private const FILTERS_PARAM = 'filters';
    private const RESOLVE_PARAM = 'resolve';
    private const SHOW_META_PARAM = 'showMeta';

    const SORT_ASC = 'asc';
    const SORT_DESC = 'desc';

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
     * @var Model
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
     * @var mixed
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
     * @var array
     */
    protected $defaultOrder = ['id', self::SORT_DESC];

    /**
     * @var array
     */
    protected $orderAble;
    /**
     * @var array
     */
    protected $filterAble;

    /** @var array */
    protected $resolveAble;

    /** @var array */
    protected $queryAble;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        if (!empty($this->modelName)) {
            $this->modelName = self::MODELS_DIR . $this->modelName;
            $this->model = new $this->modelName;
            // Todo: Remove this after moving transformer properties to models..
            $this->transformer = $this->transformer ?? $this->model->getTransformer();
            $this->orderAble = $this->model->getOrderAble();

            $this->filterAble = $this->model->getFilterAble();
            $this->resolveAble = $this->model->getResolveAble();
            $this->queryAble = $this->model->getQueryAble();

            $this->getItemsPerPageFromGet();

            $approvedResolve = [];
            $resolve = request()->input(self::RESOLVE_PARAM);
            if (is_array($resolve) && !empty($resolve)) {
                foreach ($resolve as $resolveValue) {
                    if (in_array($resolveValue, $this->resolveAble, TRUE)) {
                        $approvedResolve[] = $resolveValue;
                        if (!in_array($resolveValue, $this->model->getWith())) {
                            $this->model->addToWith(str_replace('-', '.', $resolveValue));
                        }
                    }
                }
            }

            if ($this->transformer) {
                /** @var TransformerAbstract $transformer */
                $this->transformerInstance = new $this->transformer;
                $this->transformerInstance->setDefaultIncludes($approvedResolve);
                $this->transformerInstance->setAvailableIncludes($this->model->getWith());
            }
        }
    }

    /**
     * Searches the request for 'limit' parameter;
     * If it's available, overrides the default itemsPerPage property value
     *
     * @return void
     */
    public function getItemsPerPageFromGet(): void
    {
        if (isset(request()->limit) && !empty(request()->limit)) {
            if (is_numeric(request()->limit)) {
                $itemsPerPage = (int)request()->limit;
                if (!empty($itemsPerPage) && $itemsPerPage > 0) {
                    $this->itemsPerPage = $itemsPerPage;
                }
            } elseif (strtolower(request()->limit) === self::LIMIT_ALL) {
                $this->itemsPerPage = self::LIMIT_ALL;
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
     * Applies a filter to the $items query builder.
     * Supports related models, one level deep.
     *
     * @param $items
     * @param string $filterKey
     * @param string $filterValue
     * @param string $filterOperator
     */
    private function applyFilter(&$items, string $filterKey, string $filterValue, string $filterOperator = '='): void
    {
        if (strstr($filterKey, '.')) {
            $filterKey = explode('.', $filterKey);
            if (
                !in_array($filterKey[0], $this->model->getWith(), TRUE)
                || !in_array($filterKey[1], $this->model->{$filterKey[0]}()->getRelated()->getFilterable(), TRUE)
            ) {
                return;
            }
            $items->whereHas($filterKey[0], function ($q) use ($filterKey, $filterValue, $filterOperator) {
                $q->where($filterKey[1], $filterOperator, $filterValue);
            });
            return;
        }

        if (in_array($filterKey, $this->filterAble, TRUE)) {
            $items->where($filterKey, $filterOperator, $filterValue);
        }
    }

    /**
     * Returns a collection of items from the current model after a GET request
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $items = $this->model;
        if ($request->filled(self::QUERY_PARAM) && !empty($this->queryAble) && is_array($this->queryAble)) {
            $items = $items->where(
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

        // Set default order
        if (!$request->filled(self::ORDER_BY_PARAM)) {
            $items = $items->orderBy($this->defaultOrder[0] ?? 'id', $this->defaultOrder[1] ?? self::SORT_DESC);
        }

        // Set custom order
        if (!empty($this->orderAble)) {
            // Add ordering to the index
            // nest in case the model doesn't want ordering we don't need all those checks..
            if (
                $request->filled(self::ORDER_BY_PARAM)
                && in_array($request->input(self::ORDER_BY_PARAM), $this->orderAble, TRUE)
            ) {
                $items = $items->orderBy(
                    $request->input(self::ORDER_BY_PARAM),
                    $request->{self::SORT_PARAM} === self::SORT_DESC ? self::SORT_DESC : self::SORT_ASC
                );
            }
        }

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
                                $this->applyFilter($items, $filterKey, $value, '>');
                                break;
                            case self::FILTER_LESSER_THAN:
                                $this->applyFilter($items, $filterKey, $value, '<');
                                break;
                            case self::FILTER_GREATER_THAN_OR_EQUALS:
                                $this->applyFilter($items, $filterKey, $value, '>=');
                                break;
                            case self::FILTER_LESSER_THAN_OR_EQUALS:
                                $this->applyFilter($items, $filterKey, $value, '<=');
                                break;
                            case self::FILTER_FULL_MATCH:
                            case self::FILTER_RIGHT_MATCH:
                            case self::FILTER_LEFT_MATCH:
                                $this->applyFilter($items, $filterKey, $this->getFilterLikeSearchWordValue($value, $requestOperator), 'LIKE');
                                break;
                            default:
                                $this->applyFilter($items, $filterKey, $value);
                                break;
                        }
                    }
                } else {
                    $this->applyFilter($items, $filterKey, $filterValue, '=');
                }
            }
        }

        if ($this->itemsPerPage !== self::LIMIT_ALL) {
            $items = $items->paginate($this->itemsPerPage);
            if (empty($this->transformerInstance)) {
                return $items;
            }
            $response = $this->response->paginator($items, $this->transformerInstance);
        } else {
            $items = $items->get();
            if (empty($this->transformerInstance)) {
                return $items;
            }
            $response = $this->response->collection($items, $this->transformerInstance);
        }

        if (($request->filled(self::SHOW_META_PARAM))) {
            $response->addMeta(self::ORDER_BY_PARAM, !empty($this->orderAble) ? $this->orderAble : [])
                ->addMeta(self::FILTERS_PARAM, !empty($this->filterAble) ? $this->filterAble : [])
                ->addMeta(self::QUERY_PARAM, !empty($this->queryAble) ? $this->queryAble : [])
                ->addMeta(self::RESOLVE_PARAM, !empty($this->resolveAble) ? $this->resolveAble : []);
        }

        return $response;
    }

    /**
     * Returns a single item from the current model after a GET request
     *
     * @param int $id
     *
     * @return mixed
     */
    public function show(int $id = 0)
    {
        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->response->errorNotFound();
        }

        return $this->transformerInstance
            ? $this->response->item($this->model, $this->transformerInstance)
            : $this->model;
    }

    /**
     * Inserts an item from the current model after a POST request
     * Will attempt to recover deleted items
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function store(Request $request)
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

        return $this->transformer
            ? $this->response->item($this->model, new $this->transformer)
            : $this->model;
    }

    /**
     * Updates an item from the current model after a PUT/PATCH request
     *
     * @param Request $request
     * @param int $id
     *
     * @return mixed
     */
    public function update(Request $request, int $id)
    {
        if (!$this->isValidated) {
            $this->validateInput($request, $id);
        }

        try {
            $this->model = $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            $this->response->errorNotFound();
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

        return $this->transformer
            ? $this->response->item($this->model, new $this->transformer)
            : $this->model;
    }

    /**
     * Deletes an item from the current model after a DELETE request
     *
     * @param int $id
     *
     * @return Response
     * @throws Exception
     */
    public function destroy(int $id)
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
