## NetworkController

Use the
```shell
php artisan vendor:publish
```
command to publish the `NetworkController` configuration.

* All controllers should extend it.
* For example, if we need to link the `EmployeeController`, with the `\api\employee` endpoint, add the following entry in
  the `api.php` in the `\routes` folder:

```php
    Route::resource('employee', EmployeeController::class);
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

Add the following code in the `api.php` in the `\routes` folder:

```php
    use Nevestul4o\NetworkController\Controllers\ImagesController;
    use Nevestul4o\NetworkController\Controllers\UploadController;

    Route::get('images/{width}/{name}', [ImagesController::class, 'getImage'])->name('get-image');
    Route::post('upload', [UploadController::class, 'uploadSubmit']);
```

Add the following configuration to the `.env` file:

```
UPLOADS_PATH=../uploads/files
IMAGES_PATH=../uploads/images
IMAGES_RESIZED_PATH=../cache/images
IMAGES_SUPPORTED_SIZES=300,600,900
IMAGES_REMOVE_METADATA=TRUE
```

There is a command that allows to remove all resized images:
```shell
php artisan network-controller:images-clear-cache
```

## LoginController and ChangePasswordController

* Pre-made controller classes, providing basic functionality for login/logout and password changes
* They require a Model, called **User**, placed in the `\App\Http\Models\` folder, extending **BaseUser**
* Change the namespace of the user provider in `auth.php` to `\App\Http\Models\User`
* They require a Transformer, called **UserTransformer**, placed in the `App\Http\Models\Transformers\` folder
* Functions can be freely overridden
* Can be used by defining these routes in the `api.php`:

```php
    use Nevestul4o\NetworkController\Controllers\Auth\LoginController;
    use Nevestul4o\NetworkController\Controllers\Auth\ChangePasswordController;
    
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::get('login', [LoginController::class, 'login'])->name('getCurrentUser');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::post('change-password', [ChangePasswordController::class, 'changePassword'])->name('changePassword');
    Route::post('change-password-forced', [ChangePasswordController::class, 'changePasswordForced'])->name('changePasswordForced');
```

* *WARNING* - the function `changePasswordForced` in **ChangePasswordController** is **NOT** secured!
  It can change the password of the user, without his current password.
  Take care te secure it manually, when defining the API route, or override it!
