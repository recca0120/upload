<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Illuminate\Support\Manager;
use Recca0120\Upload\Uploaders\FileAPI;
use Recca0120\Upload\Uploaders\Plupload;

class UploadManager extends Manager
{
    /**
     * __construct.
     *
     * @param $app
     * @param $request
     * @param $filesystem
     */
    public function __construct($app, Request $request = null, Filesystem $filesystem = null)
    {
        parent::__construct($app);
        $this->request = is_null($request) === true ? Request::capture() : $request;
        $this->filesystem = is_null($filesystem) === true ? new Filesystem : $filesystem;
    }

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

        return new Receiver(new FileAPI($config, $this->request, $this->filesystem), $config);
    }

    /**
     * create fileapi driver.
     *
     * @return \Recca0120\Upload\Apis\Plupload
     */
    protected function createPluploadDriver()
    {
        $config = $this->app['config']['upload'];

        return new Receiver(new Plupload($config, $this->request, $this->filesystem), $config);
    }
}
