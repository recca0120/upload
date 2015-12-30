<?php

namespace Recca0120\Upload;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected $defer = true;

    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton('ajaxupload', function ($app) {
            return new Manager($app);
        });
    }
}
