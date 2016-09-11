<?php

namespace Recca0120\Upload;

use Recca0120\Upload\Apis\FileAPI;
use Recca0120\Upload\Apis\Plupload;
use Illuminate\Support\Manager as BaseManager;

class Manager extends BaseManager
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
        return $this->app->make(ApiAdapter::class, [
            $this->app->make(FileAPI::class),
        ]);
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Apis\Plupload
     */
    protected function createPluploadDriver()
    {
        return $this->app->make(ApiAdapter::class, [
            $this->app->make(Plupload::class),
        ]);
    }
}
