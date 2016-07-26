<?php

namespace Recca0120\Upload;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('ajaxupload', function ($app) {
            return new Manager($app);
        });

        $this->app->singleton(Manager::class, function ($app) {
            return $this->app->make('ajaxupload');
        });
    }
}
