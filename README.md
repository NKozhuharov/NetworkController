# NetworkController
* All controllers should extend it.
* For example, if we need ot add the `EmployeeController`, to the `employee` endpoint, add the following entry in the `api.php` in the routes folder:
```php
  $api->resource('employee', 'EmployeeController');
```
* Refer to the follwing documentation, when using the API:

## GET Requests

#### 1. Get List

- The URL is created from the name of the resource, for example `Employee`.

  ```undefined
  /api/employee
  ```

- Every request, returns a JSON response, which is encapsulated in the parameter `data`:

  ```undefined
  {
      "data": [
          {
              <object body>
          }
      ]
  }
  ```

#### 2. Get List Pagination

- For setting the page, add the `page` parameter:

  ```undefined
  /api/employee?page=2
  ```

- For setting how many results to display per page, use the `limit` parameter. Default is 20 results per page.

  ```undefined
  /api/employee?limit=20
  ```

- For some requests, it's more convenient to get all results at once. Use `limit=all` to disable pagination. Use with caution, it may increase response time and server load.

  ```undefined
  /api/employee?limit=all
  ```

- Use the `pagination` parameter in the `meta` of every response, to help in the construction of paginated requests:

  ```undefined
  "meta": {
      "pagination": {
          "total": 20,
          "count": 5,
          "per_page": 5,
          "current_page": 2,
          "total_pages": 4,
          "links": {
              "previous": "http://hotelmaids.docker/api/employee?page=1",
              "next": "http://hotelmaids.docker/api/employee?page=3"
          }
      }
  }
  ```

#### 3. Get List Meta

- Using the parameter `showMeta` and setting it to **TRUE** or **1**, you can get useful information, about which parameters (and values) are allowed in the GET requests:

  ```
  /api/employee?showMeta=1
  ```

- The allowed fields for `orderby`, `filters`, `query`,`search` and `resolve` optional parameters are provided.

- This parameter is strictly informative and can be used safely with any other optional parameter. 

- The `showMeta` should only be used for development  purposes, because it adds a bunch of information to every response,  which increases the server load and the size of the response.

#### 4. Get List Sorting

- Use the `orderby` parameter to specify which field should be used to order the results. 

- Use the `sort` parameter to specify the direction of sorting. Allowed values **asc** and **desc**

  ```
  /api/employee?orderby=id&sort=desc
  ```

- All available fields, which can be used for sorting, are returned in the `meta` parameter `orderby`:

  ```
  "meta": {
      "orderby": [
          "id",
          "name",
          "second_name",
          "surname",
          "gender",
          "country_id"
      ]
  }
  ```

#### 5. Get List Filtering

- In every request, filters can be defined to get a response, containing entries, which match the filtering condition. Use the `filter` parameter in the GET request URL. The `filter` must be defined as array, containing the field as a key and the desired value as a value:

  ```
  /api/schedule?filters[employee_id]=6&filters[date]=2020-10-06
  ```

- Filters will be converted to a full match in the database  request, strings are allowed as values, but only the objects, which  contain the exact value of the filter will be returned in the response.

- Filers can also be used to do comparison filtering of the  results, by appending an additional array with an operator to the filter URL parts:

  ```
  /api/schedule?filters[employee_id]=6&filters[date][gt]=2020-10-06
  ```

  This will translate to `date > 2020-10-06`. Allowed operator values are:

  - `gt` - greater than, `>`
  - `lt` - lesser than, `<`
  - `gte` - greater than or equal, `>=`
  - `lte` - lesser than or equal, `<=`

  All other values of the operator array, will be ignored and the filter will be considered as `equals`. 

- All available fields, which can be used for filtering, are returned in the `meta` parameter `filters`:

  ```
  "meta": {
      "filters": [
          "employee_id",
          "room_id",
          "type_of_cleaning",
          "date"
      ]
  }
  ```

#### 6. Get List Querying

- In some situations, for example, where there is a list of  objects, it's very convenient for the user to search for a specific  term, which is present in one or more fields of the object. For example, in a list of people, which have a `First Name`, `Second Name` and `Family Name`, it's useful to search for the phrase **Tom** in all of those fields. Use the `query` parameter to search through all of the fields, which support it in the object. 

- The fields are listed in the `meta` array, under the key `query`. For example:

  ```
  "query": {
      "first_name": "%%",
      "second_name": "%%",
      "family_name": "%%"
  },
  ```

  The keys of the array are the names of the fields. The values are what type of match will be carried out:

  - `%%` or `''` - the term **Tom** is contained anywhere in the fields. Translates to f

    ```
    `first_name` LIKE '%Tom%'
    ```

  - `^%` or `%^` - the term **Tom** is at the beginning of the value. Translates to

    ```
    `first_name` LIKE 'Tom%'
    ```

  - `$%` or `%$` - the term **Tom** is at the end of the value. Translates to

    ```
    `first_name` LIKE '%Tom'
    ```

- If the `query` parameter is added, it searches for the phrase in all of the available fields:

  ```
  /api/employees?query=Tom
  ```

   Using this query, according to the `query` key in the `meta`, the following query will be carried out

  ```
  SELECT * FROM `employees` WHERE `first_name` LIKE '%Tom%' OR `second_name` LIKE '%Tom%' OR `familty_name` LIKE '%Tom%'
  ```

#### 7. Get List Searching

- When searching for a specific phrase in a specific field is required, use `search` parameter, instead of `query`.

- Searching is advanced filtering, which allows to specify what kind of match should be made against which field.

- When using `filters`, an exact match for the value is made against the specified field. For example

  ```
  /api/schedule?filters[employee_id]=6&filters[date]=2020-10-06
  ```

  will translate to

  ```
  SELECT * FROM `schedule` WHERE `employee_id` = 6 AND `date` = '2020-10-06'
  ```

- When using `search` a partial or full search is carried out for the value, in the specified field:

  ```
  /api/schedule?search[date]=2020-10-06
  ```

  will translate to

  ```
  SELECT * FROM `schedule` WHERE `date` LIKE '%2020-10-06%'
  ```

- Specifying the type of search is fully supported, provided that the `%`is url encoded: 

  ```
  /api/schedule?search[date]=2020-10-06%25
  ```

  will translate to

  ```
  SELECT * FROM `schedule` WHERE `date` LIKE '2020-10-06%'
  ```

- The supported search-able fields are listed in the `meta` array, under the key `search`. For example:

  ```
  "search": [
      "date"
  ],
  ```

#### 8. Get List Resolving

- Objects, which are related to other objects, have the possibility to be returned in the response, adding the full body of the related  child object.

- Use the `resolve` parameter to define which objects should be added:

  ```
  /api/schedule?resolve[]=room
  ```

- For resolving objects, which are related, using a 3rd object, add a dash (**-**) in the related object name, to indicate indirect (through) relationship:

  ```
  /api/schedule?resolve[]=room&resolve[]=room-room_type
  ```

- The `resolve` parameter also supports handling indexes in the request, for example:

  ```
  /api/schedule?resolve[0]=room&resolve[1]=room-room_type
  ```

- All available resolvable objects are defined in the `resolve` parameter in the `meta`:

  ```
  "meta": {
      "resolve": [
          "room",
          "room-room_type"
      ]
  }
  ```

- Nested objects will be added in the body of every object in the  response. Objects, nested through a second object, will also be added    directly in the body of the requested object. For example, the response  of the request above should look like:

  ```undefined
  {
      "id": 190,
      "employee_id": 6,
      "room_id": 28,
      "type_of_cleaning": "D",
      "date": "2020-10-05",
      "room": {
          "data": {
              "id": 28,
              "customer_id": 5,
              "room_type_id": 19,
              "room_inventory_template_id": 8,
              "name": "154",
              "floor": 3
          }
      },
      "room-room_type": {
          "data": {
              "id": 19,
              "customer_id": 5,
              "name": "room_types_4451C9D4CD"
          }
      }
  }
  ```

#### 9. Get List Querying and Resolved Objects

- When using resolved objects, together with querying (using `query` keyword), the search will also include all query-able properties of the resolved objects as well. This possibility will be indicated in the `query` parameter in the `meta`, with the value `related`. For example, for work logs:

  ```
  "query": {
      "time_start": "%%",
      "employee": "related",
      "room": "related"
  }
  ```

- For example, the request

  ```undefined
  /api/work-log?resolve[]=room&query=355
  ```

  in addition with the `query` parameter in the `room` meta being

  ```undefined
  "query": {
      "name": "%%",
      "floor": "%%"
  },
  ```

  will translate to:

  ```undefined
  SELECT * FROM `work_log` LEFT JOIN `room` ON `work_log`.`room_id` = `room`.`id` WHERE `work_log`.`time_start` LIKE '%355%' OR `room`.`name` LIKE '%355%' OR `room`.`floor` LIKE '%355%'
  ```

#### 10. Get List Filtering by Resolved Objects Properties

- When using resolved objects, filtering by their properties is  also possible. The syntax is the same, like when filtering by properties of the requested object, by using the keyword `filter` in  the GET URL. To explain, that this is a field of a related object, add  the name of the related object, before the field name and concatenate  them with a dot (`.`). For example:

  ```undefined
  /api/work-log?filters[room.customer_id]=2&resolve[]=room
  ```

- Keep in mind, that filtering by the properties of a related  object is only possible, when this object is resolved in the request.  For example,

  ```undefined
  /api/work-log?filters[room.customer_id]=2
  ```

  will not work and the filter `room.customer_id` will be ignored.

#### 11. Get a single object

- It's possible to get only one result of a resource, by specifying it's id:

  ```undefined
  /api/employee/3
  ```

- The response will contain only one object, encapsulated in the `data` variable

# `FileUploadController` and `ImageUploadController`:
Add the following code in the `api.php` in the routes folder:
```php
$api->group(
            ['namespace' => 'Nevestul4o\NetworkController\Controllers'],
            static function ($api) {
                $api->get('images/{width}/{name}', 'ImagesController@getImage')->name('getImage');
                $api->group(
                    ['middleware' => 'auth'],
                    static function ($api) {
                        $api->post('upload', 'UploadController@uploadSubmit');
                    }
                );
            }
        );
```
