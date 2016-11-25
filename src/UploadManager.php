<?php

namespace Recca0120\Upload;

use Illuminate\Support\Manager;
use Recca0120\Upload\Apis\FileAPI;
use Recca0120\Upload\Apis\Plupload;

class UploadManager extends Manager
{
    /**
     * default driver.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return 'fileapi';
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Apis\FileAPI
     */
    protected function createFileapiDriver()
    {
        $config = $this->app['config']['upload'];

        return $this->app->make(ApiAdapter::class, [
            $this->app->make(FileAPI::class),
            'config' => $config,
        ]);
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Apis\Plupload
     */
    protected function createPluploadDriver()
    {
        $config = $this->app['config']['upload'];

        return $this->app->make(ApiAdapter::class, [
            $this->app->make(Plupload::class),
            'config' => $config,
        ]);
    }
}
