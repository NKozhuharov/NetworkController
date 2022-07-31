<?php


namespace Nevestul4o\NetworkController;

use Illuminate\Support\ServiceProvider;
use Nevestul4o\NetworkController\Console\ImagesClearCache;

class NetworkControllerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImagesClearCache::class,
            ]);
        }
        $this->publishes([
            __DIR__.'/Config/networkcontroller.php' => config_path('networkcontroller.php'),
        ]);
    }
}
