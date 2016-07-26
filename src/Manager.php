<?php

namespace Recca0120\Upload;

use Illuminate\Container\Container;
use Illuminate\Support\Manager as BaseManager;

class Manager extends BaseManager
{
    /**
     * constructor.
     *
     * @param \Illuminate\Container\Container $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * default driver.
     *
     * @return [type] [description]
     */
    public function getDefaultDriver()
    {
        return 'fileapi';
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Driver\FileApi
     */
    protected function createFileapiDriver()
    {
        return $this->app->make(Uploader::class, [
            $this->app->make(FileApi::class),
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
