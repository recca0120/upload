<?php

namespace Recca0120\Upload;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/upload.php', 'upload');
        $this->app->singleton(Manager::class, function ($app) {
            return new Manager($app);
        });
    }
}
