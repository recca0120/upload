<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Manager;
use Recca0120\Upload\Drivers\Dropzone;
use Recca0120\Upload\Drivers\FileAPI;
use Recca0120\Upload\Drivers\FilePond;
use Recca0120\Upload\Drivers\FineUploader;
use Recca0120\Upload\Drivers\Plupload;

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
        return $this->makeReceiver(Dropzone::class);
    }

    protected function createFileapiDriver(): Receiver
    {
        return $this->makeReceiver(FileAPI::class);
    }

    protected function createFineUploaderDriver(): Receiver
    {
        return $this->makeReceiver(FineUploader::class);
    }

    protected function createPluploadDriver(): Receiver
    {
        return $this->makeReceiver(Plupload::class);
    }

    protected function createFilePond(): Receiver
    {
        return $this->makeReceiver(FilePond::class);
    }

    private function makeReceiver($class): Receiver
    {
        return new Receiver(new $class($this->getConfig(), $this->request, $this->files));
    }

    private function getConfig()
    {
        return $this->container['config']['upload'];
    }
}
