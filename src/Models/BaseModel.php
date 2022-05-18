<?php

namespace Nevestul4o\NetworkController\Models;

use Illuminate\Database\Eloquent\Model;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\NetworkController;
use Nevestul4o\NetworkController\Transformers\GenericTransformer;

abstract class BaseModel extends Model
{
    const DATA = 'data';

    const F_ID = 'id';

    const F_CREATED_AT = self::CREATED_AT;
    const F_UPDATED_AT = self::UPDATED_AT;
    const F_DELETED_AT = 'deleted_at';

    const CAST_TIMESTAMP = 'timestamp';
    const CAST_ARRAY = 'array';

    const QUERYABLE_RELATED = 'related';
    const QUERYABLE_LEFT_MATCH = NetworkController::FILTER_LEFT_MATCH;
    const QUERYABLE_RIGHT_MATCH = NetworkController::FILTER_RIGHT_MATCH;
    const QUERYABLE_FULL_MATCH = NetworkController::FILTER_FULL_MATCH;

    /**
     * In case we have values we can order by, custom and default
     *
     * @var array
     */
    protected array $orderAble;

    /**
     * In case we have values we can filter by, no filter default
     *
     * @var array
     */
    protected array $filterAble;

    /**
     * In case the object allows resolving of child objects, we can add them here.
     * The objects MUST be compatible with the model relations.
     * For nested relations, declared using a period (.), like 'parent.child',
     * when declaring the resolveAble, exchange the period with a dash (parent-child).
     *
     * @var array
     */
    protected array $resolveAble;

    /**
     * @var array
     */
    protected array $aggregateAble;

    /**
     * The API fractal transformer class
     *
     * @var string
     */
    protected string $transformerClass = GenericTransformer::class;

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        self::F_DELETED_AT,
    ];

    /**
     * @var array ['column' => '','column' =>'*%','column' => '%*','column' => '%%'] query parameter fields
     */
    protected array $queryAble = [];

    /**
     * @return string
     */
    public function getTransformerClass(): string
    {
        return $this->transformerClass;
    }

    /**
     * @return array
     */
    public function getQueryAble(): array
    {
        return $this->queryAble ?? [];
    }


    /**
     * @return array
     */
    public function getOrderAble(): array
    {
        return $this->orderAble ?? [];
    }

    /**
     * @return array
     */
    public function getFilterAble(): array
    {
        return $this->filterAble ?? [];
    }

    /**
     * @return array
     */
    public function getResolveAble(): array
    {
        return $this->resolveAble ?? [];
    }

    /**
     * @return array
     */
    public function getAggregateAble(): array
    {
        return $this->aggregateAble ?? [];
    }

    /**
     * Adds a new relation to the relation list
     *
     * @param string $relation - the new relation to add
     */
    public function addToWith(string $relation): void
    {
        $this->with[] = $relation;
    }

    /**
     * Removes an existing relation to the relation list
     *
     * @param string $key
     */
    public function removeWith(string $key): void
    {
        unset($this->with[$key]);
    }

    /**
     * Gets an array of the fields, related to the object.
     *
     * Fixed to work alongside the with() and without() eager loading methods
     *
     * @return array
     */
    public function getWith(): array
    {
        return array_keys($this->relationsToArray()) ?: $this->with;
        // This is still a hack for storing relationships by id
        // the system is unable to load them that way...
    }

    /**
     * Gets an array of the fields, related to the object without the eager loaded
     *
     * @return array
     */
    public function getWithRelation(): array
    {
        return array_keys($this->relationsToArray());
    }

    /**
     * Check if the model can has translatable attributes.
     * Requires Laravel Translatable package.
     * @link https://docs.astrotomic.info/laravel-translatable/
     *
     * @return bool
     */
    public function isTranslatable(): bool
    {
        return trait_exists('\Astrotomic\Translatable\Translatable') && in_array('Astrotomic\Translatable\Translatable', class_uses($this));
    }

    /**
     * Check it fhe model is using the Laravel's SoftDeletes trait
     *
     * @return bool
     */
    public function isSoftDeletable(): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this));
    }

    /**
     * Get all translatable attributes from the model.
     * Requires Laravel Translatable package.
     * @link https://docs.astrotomic.info/laravel-translatable/
     *
     * @return array|null
     */
    public function getTranslatedAttributes(): ?array
    {
        if (!$this->isTranslatable()) {
            return [];
        }

        return $this->translatedAttributes;
    }
}
