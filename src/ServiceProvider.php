<?php

namespace Recca0120\Upload;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    protected $defer = true;

    public function boot()
    {
        $this->handlePublishes();
    }

    public function register()
    {
        $this->app->singleton('ajaxupload', function ($app) {
            return new Manager($app);
        });
    }

    protected function handlePublishes()
    {
        $this->publishes([
            __DIR__.'/../config/upload.php' => config_path('upload.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/upload.php', 'upload');
    }
}
