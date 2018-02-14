<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Recca0120\Upload\Dropzone;
use Illuminate\Support\Manager;

class UploadManager extends Manager
{
    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * $files.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $files;

    /**
     * __construct.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Http\Request $request
     * @param \Recca0120\Upload\Filesystem $files
     */
    public function __construct($app, Request $request = null, Filesystem $files = null)
    {
        parent::__construct($app);
        $this->request = $request ?: Request::capture();
        $this->files = $files ?: new Filesystem();
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
     * create fine uploader driver.
     *
     * @return Receiver
     */
    protected function createDropzoneDriver()
    {
        return new Receiver(new Dropzone($this->app['config']['upload'], $this->request, $this->files));
    }

    /**
     * create fileapi driver.
     *
     * @return Receiver
     */
    protected function createFileapiDriver()
    {
        return new Receiver(new FileAPI($this->app['config']['upload'], $this->request, $this->files));
    }

    /**
     * create fine uploader driver.
     *
     * @return Receiver
     */
    protected function createFineUploaderDriver()
    {
        return new Receiver(new FineUploader($this->app['config']['upload'], $this->request, $this->files));
    }

    /**
     * create plupload driver.
     *
     * @return Receiver
     */
    protected function createPluploadDriver()
    {
        return new Receiver(new Plupload($this->app['config']['upload'], $this->request, $this->files));
    }
}
