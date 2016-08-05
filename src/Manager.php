<?php

namespace Recca0120\Upload;

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
     * @return \Recca0120\Upload\Driver\FileAPI
     */
    protected function createFileapiDriver()
    {
        return $this->app->make(Uploader::class, [
            $this->app->make(FileAPI::class),
        ]);
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Driver\Plupload
     */
    protected function createPluploadDriver()
    {
        return $this->app->make(Uploader::class, [
            $this->app->make(Plupload::class),
        ]);
    }
}
