<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Manager;

class UploadManager extends Manager
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Filesystem
     */
    protected $files;

    public function __construct(Container $container, Request $request = null, Filesystem $files = null)
    {
        parent::__construct($container);
        $this->request = $request ?: Request::capture();
        $this->files = $files ?: new Filesystem();
    }

    public function getDefaultDriver(): string
    {
        return 'fileapi';
    }

    protected function createDropzoneDriver(): Receiver
    {
        return new Receiver(new Dropzone($this->container['config']['upload'], $this->request, $this->files));
    }

    protected function createFileapiDriver(): Receiver
    {
        return new Receiver(new FileAPI($this->container['config']['upload'], $this->request, $this->files));
    }

    protected function createFineUploaderDriver(): Receiver
    {
        return new Receiver(new FineUploader($this->container['config']['upload'], $this->request, $this->files));
    }

    protected function createPluploadDriver(): Receiver
    {
        return new Receiver(new Plupload($this->container['config']['upload'], $this->request, $this->files));
    }

    protected function createFilePond(): Receiver
    {
        return new Receiver(new FilePond($this->container['config']['upload'], $this->request, $this->files));
    }
}
