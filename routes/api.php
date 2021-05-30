<?php

// Create Dingo Router
$api = app('Dingo\Api\Routing\Router');

// Create a Dingo Version Group
$api->version(
    'v1',
    ['middleware' => ['api']],
    static function ($api) {
        $api->get(
            '',
            function () {
                return 'success';
            }
        );

        $api->group(
            ['namespace' => 'Nevestul4o\NetworkController\Controllers'],
            static function ($api) {
                $api->post('login', 'Auth\LoginController@login')->name('login');
                $api->get('login', 'Auth\LoginController@getCurrentUser')->name('getCurrentUser');
                $api->get('logout', 'Auth\LoginController@logout')->name('logout');

                $api->get('images/{width}/{name}', 'ImagesController@getImage')->name('getImage');
                $api->group(
                    ['middleware' => 'auth'],
                    static function ($api) {
                        $api->post('change-password', 'Auth\ChangePasswordController@changePassword')->name('changePassword');
                        $api->post('upload', 'UploadController@uploadSubmit');
                    }
                );

                $api->group(
                    ['middleware' => ['auth', 'userisadmin']],
                    static function ($api) {
                        $api->post('change-password-forced', 'Auth\ChangePasswordController@changePasswordForced')->name('changePasswordForced');
                    }
                );
            }
        );

        $api->group(
            ['namespace' => 'App\Http\Controllers'],
            static function ($api) {
                $api->get('static-data', 'StaticDataController@getData')->name('getData');

                $api->group(
                    ['middleware' => 'auth'],
                    static function ($api) {
                        $api->resource('employee', 'EmployeeController');
                        $api->resource('customer', 'CustomerController');
                        $api->resource('customer-contact', 'CustomerContactController');
                        $api->resource('minibar-item', 'MinibarItemController');
                        $api->resource('minibar-item-movement', 'MinibarItemMovementController');
                        $api->resource('room-item', 'RoomItemController');
                        $api->resource('room-type', 'RoomTypeController');
                        $api->resource('room-inventory-template', 'RoomInventoryTemplateController');
                        $api->resource('room', 'RoomController');
                        $api->resource('work-log', 'WorkLogController');
                        $api->resource('schedule', 'ScheduleController');
                        $api->resource('working-hours', 'WorkingHoursController');
                    }
                );
            }
        );
    }
);
