<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Illuminate\Support\Manager;
use Recca0120\Upload\Apis\FileAPI;
use Recca0120\Upload\Apis\Plupload;

class UploadManager extends Manager
{
    /**
     * $request.
     *
     * @var [type]
     */
    protected $request;

    /**
     * $filesystem.
     *
     * @var [type]
     */
    protected $filesystem;

    /**
     * __construct.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Http\Request $request
     * @param Filesystem $filesystem
     */
    public function __construct($app, Request $request = null, Filesystem $filesystem = null)
    {
        parent::__construct($app);
        $this->request = $request ?: Request::capture();
        $this->filesystem = $filesystem ?: new Filesystem();
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
     * @return Receiver
     */
    protected function createFileapiDriver()
    {
        return new Receiver(new FileAPI($this->app['config']['upload'], $this->request, $this->filesystem));
    }

    /**
     * create fileapi driver.
     *
     * @return Receiver
     */
    protected function createPluploadDriver()
    {
        return new Receiver(new Plupload($this->app['config']['upload'], $this->request, $this->filesystem));
    }
}
