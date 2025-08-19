# NetworkController

A Laravel package that provides a powerful, convention-driven base API controller and model foundation for building RESTful endpoints quickly. 
It includes rich GET query capabilities (pagination, filtering, sorting, search, includes, aggregations), 
transformers via Fractal, file and image upload helpers, ready-made auth controllers, and more.

## Table of Contents
- Requirements
- Installation
- Publish Configuration
- Quick Start
- Models (BaseModel)
- Special Features and Aggregations
- File and Image Uploads
- Auth Controllers (Login and Change Password)
- GET Requests Reference
- Contributing
- License

## Requirements
- PHP: ^8.0.2
- Laravel: ^9.0 | ^10.0 | ^11.0 | ^12.0
- Extensions: ext-imagick (required for image features)

## Installation
Install via Composer:

```bash
  composer require nevestul4o/network-controller
```

## Publish Configuration
The package ships with a publishable configuration file. Use:

```bash
  php artisan vendor:publish --provider="Nevestul4o\NetworkController\NetworkControllerServiceProvider"
```

This will publish:
- config/networkcontroller.php

## Quick Start
1) Create a controller that extends NetworkController and register a resource route:

```php
use App\Http\Controllers\Controller;
use Nevestul4o\NetworkController\NetworkController;

class APIEmployeeController extends NetworkController {}
```

In routes/api.php:

```php
use App\Http\Controllers\APIEmployeeController;
use Illuminate\Support\Facades\Route;

Route::resource('employee', APIEmployeeController::class);
```

2) Create a model that extends BaseModel and place it under `App\Http\Models` (as expected by this package):

```php
namespace App\Http\Models;

use Nevestul4o\NetworkController\Models\BaseModel;

class Employee extends BaseModel
{
    protected $fillable = ['first_name', 'last_name'];
}
```

3) Optional: Add a Fractal transformer for your model and configure includes as needed.

Now you can call endpoints like GET /api/employee with rich query parameters (see reference below).

## Models (BaseModel)
- Abstract class; your application models should extend it.
- Expected location: `App\Http\Models`.
- Ideally, one model maps to one controller that extends NetworkController.

## Special Features and Aggregations
- Advanced ordering (including virtual keys via scopes)
- Safe, predefined aggregations returned under meta.aggregate

Read more and see examples: [BaseModel — Examples](./BaseModelSpecialFeaturesExamples.md).

## File and Image Uploads
Add these routes to routes/api.php:

```php
use Nevestul4o\NetworkController\Controllers\ImagesController;
use Nevestul4o\NetworkController\Controllers\UploadController;

Route::get('images/{width}/{name}', [ImagesController::class, 'getImage'])->name('get-image');
Route::post('upload', [UploadController::class, 'uploadSubmit']);
```

Configure your .env:

```
UPLOADS_PATH=../uploads/files
IMAGES_PATH=../uploads/images
IMAGES_RESIZED_PATH=../cache/images
IMAGES_SUPPORTED_SIZES=300,600,900
IMAGES_REMOVE_METADATA=TRUE
```

Maintenance command to clear the resized image cache:

```bash
  php artisan network-controller:images-clear-cache
```

## Auth Controllers (Login and Change Password)
- Pre-made controllers offering login/logout and password change flows.
- Requires a `User` model under `App\Http\Models` extending `BaseUser`.
- Update the user provider namespace in config/auth.php to `App\Http\Models\User`.
- Requires a `UserTransformer` under `App\Http\Models\Transformers`.
- You can override methods as needed.

Routes (add to routes/api.php):

```php
use Nevestul4o\NetworkController\Controllers\Auth\LoginController;
use Nevestul4o\NetworkController\Controllers\Auth\ChangePasswordController;

Route::post('login', [LoginController::class, 'login'])->name('login');
Route::get('login', [LoginController::class, 'login'])->name('getCurrentUser');
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::post('change-password', [ChangePasswordController::class, 'changePassword'])->name('changePassword');
Route::post('change-password-forced', [ChangePasswordController::class, 'changePasswordForced'])->name('changePasswordForced');
```

Warning: changePasswordForced in ChangePasswordController is NOT secured. It can change a user's password without the current password. Protect the route appropriately or override the method.

## GET Requests Reference
For detailed documentation of the supported GET parameters (pagination, showMeta, sorting, filtering, search, resolve/includes, slugs, aggregations, and more), see:

- [NetworkController GET Requests](./NetworkControllerGETRequests.md)

These docs explain how to use parameters like page, limit, orderby, sort, filters, query, resolve, aggregate and how meta.route_info helps discover allowed values.

## Contributing
Issues and pull requests are welcome. Please follow the conventional code style and include tests where possible.

## License
MIT
