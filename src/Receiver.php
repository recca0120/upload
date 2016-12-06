<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Recca0120\Upload\Uploader\Uploader;
use Recca0120\Upload\Exception\ChunkedResponseException;

class Receiver
{
    protected $uploader;

    protected $filesystem;

    protected $config;

    public function __construct(Uploader $uploader, Filesystem $filesystem, $config = [])
    {
        $this->uploader = $uploader;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    public function receive($name, Closure $closure)
    {
        $path = Arr::get($this->config, 'path');
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        try {
            $uploadedFile = $this->uploader
                ->setPath($path)
                ->get($name);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }

        $response = $closure($uploadedFile);
        $file = $uploadedFile->getPathname();
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }
        $this->clean($path);

        return $this->uploader->completedResponse($response);
    }

    protected function clean($path)
    {
        $time = time();
        $maxFileAge = 3600;
        foreach ($this->filesystem->files($path) as $file) {
            if ($this->filesystem->exists($file) === true && $this->filesystem->lastModified($file) < ($time - $maxFileAge)) {
                $this->filesystem->delete($file);
            }
        }
    }
}
