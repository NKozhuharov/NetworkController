## NetworkController
* All controllers should extend it.
* For example, if we need ot add the `EmployeeController`, to the `employee` endpoint, add the following entry in the `api.php` in the routes folder:
```php
    $api->resource('employee', 'EmployeeController');
````

## BaseModel
* Abstract class, cannot be used standalone
* All models should extend it
* All models need to be placed in the `\App\Http\Models\` folder
* Ideally, one model should have one controller, which extends **NetworkController**

## BaseUser
* Abstract class, cannot be used standalone
* Provides basic user account functionality
* It **MUST** be extended by a model called **User**, placed in the `\App\Http\Models\` folder

## FileUploadController and ImageUploadController
Add the following code in the `api.php` in the routes folder:
```php
    $api->group(
        ['namespace' => 'Nevestul4o\NetworkController\Controllers'],
        static function ($api) {
            $api->get('images/{width}/{name}', 'ImagesController@getImage')->name('getImage');
            $api->post('upload', 'UploadController@uploadSubmit');
        }
    );
```
## LoginController and ChangePasswordController
* Pre-made controller classes, providing basic functionality for login/logout and password changes
* They require a Model, called **User**, placed in the `\App\Http\Models\` folder, extending **BaseUser**
* Change the namespace of the user provider in `auth.php` to `\App\Http\Models\User`
* They require a Transformer, called **UserTransformer**, placed in the `App\Http\Models\Transformers\` folder
* Functions can be freely overridden
* Can be used by defining these routes in the `api.php`:
```php
    $api->post('login', 'Auth\LoginController@login')->name('login');
    $api->get('login', 'Auth\LoginController@getCurrentUser')->name('getCurrentUser');
    $api->get('logout', 'Auth\LoginController@logout')->name('logout');
    
    $api->post('change-password', 'Auth\ChangePasswordController@changePassword')->name('changePassword');
    $api->post('change-password-forced', 'Auth\ChangePasswordController@changePasswordForced')->name('changePasswordForced');
```
* *WARNING* - the function `changePasswordForced` in **ChangePasswordController** is **NOT** secured! 
Take care te secure it manually, when defining the API route, or override it!
