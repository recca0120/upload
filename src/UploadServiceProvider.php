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

        $aliasName = 'Recca0120\Upload\Manager';
        class_alias(UploadManager::class, $aliasName);

        $this->app->singleton(UploadManager::class, function ($app) {
            return new UploadManager($app);
        });
        $this->app->singleton($aliasName, UploadManager::class);
    }
}
