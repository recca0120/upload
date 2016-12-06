<?php

namespace Recca0120\Upload;

use Illuminate\Support\ServiceProvider;

class UploadServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/upload.php', 'upload');

        $this->app->singleton(Filesystem::class, Filesystem::class);
        $this->app->singleton(UploadManager::class, function ($app) {
            return new UploadManager($app);
        });
        $this->app->singleton(Manager::class, UploadManager::class);
    }
}
