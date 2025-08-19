# BaseModel — Examples

This document provides practical examples for common BaseModel-related features used by NetworkController.

## Ordering with scopes

Define custom ordering using local scopes when the requested order-by key is not a real/fillable column of the model.

- List the allowed order-by keys via the protected array property `$orderAble` on your model.
- If an entry in `$orderAble` is not a fillable column, implement a matching local scope using `scopeOrderBy{StudlyCase}`.

Example:

```php
use Nevestul4o\NetworkController\Models\BaseModel;

class Employee extends BaseModel
{
    protected $fillable = ['first_name', 'last_name'];

    protected array $orderAble = ['first_name', 'last_name', 'full_name'];

    // Custom ordering for a virtual key from orderAble
    public function scopeOrderByFullName($query, string $sort)
    {
        return $query->orderByRaw("CONCAT(first_name, ' ', last_name) " . ($sort === 'desc' ? 'DESC' : 'ASC'));
    }
}
```

Notes:
- When `orderBy` equals a fillable column listed in `$orderAble`, a standard `orderBy(column, sort)` is applied automatically.
- When `orderBy` equals a custom key in `$orderAble` (not a fillable column), the matching scope method will be invoked as shown above.

## Aggregations (aggregateAble)

You can expose safe, predefined aggregations that API consumers can request on listing endpoints. The controller executes aggregation methods you expose and returns the results in the response meta under the `aggregate` key.

How it works:
- In your model, whitelist aggregation names via `protected array $aggregateAble`.
- In your controller (which extends NetworkController), implement a method per aggregation with the name pattern `aggregate_{name}`.
- Clients request aggregations by adding `?aggregate[]=name1&aggregate[]=name2` to the query string.

Model example:

```php
use Nevestul4o\NetworkController\Models\BaseModel;

class Employee extends BaseModel
{
    protected $fillable = ['first_name', 'last_name', 'salary', 'status', 'department_id'];

    // These are the only aggregation names that clients are allowed to request
    protected array $aggregateAble = ['total_count', 'sum_salary'];
}
```

Controller example:

```php
use Illuminate\Database\Eloquent\Builder;
use Nevestul4o\NetworkController\NetworkController;

class APIEmployeeController extends NetworkController
{
    protected string $modelClass = Employee::class;

    // Optional: you can leave this out; NetworkController will copy from the model in its constructor
    // protected array $aggregateAble = ['total_count', 'sum_salary'];

    // GET /api/employee?aggregate[]=total_count&aggregate[]=sum_salary

    protected function aggregate_total_count(Builder $builder)
    {
        return $builder->count();
    }

    protected function aggregate_sum_salary(Builder $builder)
    {
        return (float) $builder->sum('salary');
    }
}
```

Response shape (simplified):

```
{
  "data": [ ... paginated items ... ],
  "meta": {
    "aggregate": {
      "total_count": 1234,
      "sum_salary": 987654.32
    }
  }
}
```

Notes:
- Only names listed in the model’s `$aggregateAble` and having a matching controller method `aggregate_{name}` will be executed.
- Each aggregation method receives the current Eloquent Builder with all filters, search, and joins already applied.
- Return a scalar (int/float/string) or a small array; it will be placed under `meta.aggregate[name]`.

Back to README section: [BaseModel](./README.md#basemodel)
