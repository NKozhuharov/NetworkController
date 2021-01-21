## NetworkController
* All controllers should extend it.
* For example, if we need ot add the `EmployeeController`, to the `employee` endpoint, add the following entry in the `api.php` in the routes folder:
```php
  $api->resource('employee', 'EmployeeController');
````

## FileUploadController and ImageUploadController
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
