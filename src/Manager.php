<?php

namespace Recca0120\Upload;

use Illuminate\Support\Manager as BaseManager;

class Manager extends BaseManager
{
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function getDefaultDriver()
    {
        return 'plupload';
    }

    protected function createFileapiDriver()
    {
        return $this->app->make(\Recca0120\Upload\Services\FileApi::class);
    }

    protected function createPluploadDriver()
    {
        return $this->app->make(\Recca0120\Upload\Services\Plupload::class);
    }
}
