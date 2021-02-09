<?php

namespace Nevestul4o\NetworkController\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use League\Fractal\TransformerAbstract;
use Nevestul4o\NetworkController\NetworkController;

abstract class BaseModel extends Model
{
    use SoftDeletes;

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
    protected $orderAble;

    /**
     * In case we have values we can filter by, no filter default
     *
     * @var array
     */
    protected $filterAble;

    /**
     * In case the object allows resolving of child objects, we can add them here.
     * The objects MUST be compatible with the model relations.
     * For nested relations, declared using a period (.), like 'parent.child',
     * when declaring the resolveAble, exchange the period with a dash (parent-child).
     *
     * @var array
     */
    protected $resolveAble;

    /**
     * @var array
     */
    protected $aggregateAble;

    /**
     * If we are using a transformer apply the API transformer casing
     *
     * @var TransformerAbstract
     */
    protected $transformer;

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
    protected $queryAble = [];

    /**
     * A quick method to get table names for migrations
     *
     * @return mixed
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }

    /**
     * @return TransformerAbstract
     */
    public function getTransformer()
    {
        return $this->transformer;
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
    public function getOrderAble()
    {
        return $this->orderAble ?? [];
    }

    /**
     * @return array
     */
    public function getFilterAble()
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
    public function addToWith(string $relation)
    {
        $this->with[] = $relation;
    }

    /**
     * Removes an existing relation to the relation list
     *
     * @param string $key
     */
    public function removeWith(string $key)
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
    public function getWith()
    {
        return array_keys($this->relationsToArray()) ?: $this->with; // This is still a hack for storing relationships by id
        // the system is unable to load them that way..
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
     * Creates or updates an entry in the database, using the provided array.
     * Initializes model fillables from the array elements.
     *
     * @param array $data
     */
    public function saveFromArray(array $data): void
    {
        foreach ($this->getFillable() as $fillable) {
            if (isset($data[$fillable])) {
                $this->$fillable = $data[$fillable];
            }
        }
        $this->save();
    }

    /**
     * Allows to store data for multiple languages in one attribute
     *
     * @param $attributeName
     * @param $value
     */
    protected function setAttributeTranslation($attributeName, $value)
    {
        $attribute = [];
        if ($this->{$attributeName}) {
            $attribute = $this->{$attributeName};
        }
        $attribute[app()->getLocale()] = $value;

        $this->attributes[$attributeName] = json_encode($attribute);
    }
}
