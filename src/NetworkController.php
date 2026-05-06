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
use Nevestul4o\NetworkController\Filters\FilterOperators;
use Nevestul4o\NetworkController\Filters\QueryFilterService;
use Nevestul4o\NetworkController\Traits\AuthorizeRequest;
use Nevestul4o\NetworkController\Transformers\Interface\NestedIncludesTransformer as NestedIncludesInterface;
use Nevestul4o\NetworkController\Translation\TranslationService;

abstract class NetworkController extends BaseController
{
    use ValidatesRequests;
    use AuthorizeRequest;

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
     * @var int|string
     */
    protected int|string $itemsPerPage = 20;

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
    public function __construct(
        protected QueryFilterService $filterService,
        protected TranslationService $translationService,
    )
    {
        if (empty($this->modelClass)) {
            throw new Exception("Define a model class in order to use NetworkController");
        }

        $this->model = new $this->modelClass;

        $this->middleware(function ($request, $next) {
            match ($request->route()?->getActionMethod()) {
                'store' => $this->authorizeStore($request),
                'update' => $this->authorizeUpdate($request),
                'destroy' => $this->authorizeDestroy($request),
                default => null,
            };

            return $next($request);
        });

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
        $resolve         = array_merge($this->defaultResolve, request()->array(self::RESOLVE_PARAM));
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
     * Searches the request for 'limit' parameter;
     * If it's available, overrides the default itemsPerPage property value
     *
     * @param  Request  $request
     * @return int|string
     */
    protected function getItemsPerPageFromRequest(Request $request): int|string
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
        $queryWord = $request->get(self::QUERY_PARAM);
        if (!is_string($queryWord) || empty($queryWord) || empty($this->queryAble)) {
            return;
        }

        foreach ($this->queryAble as $column => $operators) {
            if (is_numeric($column)) {
                $column = $operators; //if the column is numeric, there is no explicit operator set
            }
            $this->translationService->joinTranslationModelTableIfNecessary($this->model, $column, $builder);
        }

        $builder = $builder->where(
            function ($query) use ($queryWord) {
                foreach ($this->queryAble as $column => $operators) {
                    //if the column is numeric, there is no explicit operator set, use the default %% operator
                    if (is_numeric($column)) {
                        $column    = $operators;
                        $operators = FilterOperators::FILTER_FULL_MATCH;
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
                                            $this->filterService->getFilterLikeSearchWordValue($queryWord, $relatedQueryableOperators)
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
                                        $this->filterService->getFilterLikeSearchWordValue($queryWord, $relatedQueryableOperators)
                                    );
                                }
                            );
                        }
                    } elseif (!in_array($column, $this->resolveAble)) {
                        $query->orWhere(
                            $column,
                            'LIKE',
                            $this->filterService->getFilterLikeSearchWordValue($queryWord, $operators)
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

        $sort = ($sort && is_string($sort)) ? trim(strtolower($sort)) : $this->defaultSort;
        if ($sort !== self::SORT_ASC && $sort !== self::SORT_DESC) {
            $sort = $this->defaultSort;
        }

        $orderBy = ($orderBy && is_string($orderBy)) ? trim(strtolower($orderBy)) : $this->defaultOrder;

        if (in_array($orderBy, $this->orderAble)) {
            if ($this->model->isTranslatable() && $this->model->isTranslationAttribute($orderBy)) {
                $this->translationService->joinTranslationModelTableIfNecessary($this->model, $orderBy, $builder);
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
                $this->translationService->joinTranslationModelTableIfNecessary($relation->getRelated(), $orderBy[1], $orderByInnerQuery);

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

        $this->filterService->applyFilters($builder, $this->model, $filters);
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
                self::FILTERS_PARAM   => [
                    'attributes' => !empty($this->filterAble) ? $this->filterAble : [],
                    'relations'  => !empty($this->filterAbleRelations) ? $this->filterAbleRelations : [],
                ],
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

        if ($request->filled(self::AGGREGATE_PARAM)) {
            $aggregations = [];
            foreach ($request->array(self::AGGREGATE_PARAM) as $aggregation) {
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
        } catch (ModelNotFoundException) {
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
        } catch (ModelNotFoundException) {
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
