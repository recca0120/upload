<?php

namespace Recca0120\Upload;

use Closure;
use Illuminate\Support\Arr;
use Recca0120\Upload\Contracts\Uploader;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class Receiver
{
    /**
     * $uploader.
     *
     * @var \Recca0120\Upload\Contracts\Uploader
     */
    protected $uploader;

    /**
     * $filesystem.
     *
     * @var \Recca0120\Upload\Filesystem
     */
    protected $filesystem;

    /**
     * $config.
     *
     * @var array
     */
    protected $config;

    /**
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Uploader   $uploader
     * @param \Recca0120\Upload\Filesystem $filesystem
     * @param array     $config
     */
    public function __construct(Uploader $uploader, Filesystem $filesystem, $config = [])
    {
        $this->uploader = $uploader;
        $this->filesystem = $filesystem;
        $this->config = $config;
    }

    /**
     * receive.
     *
     * @param  string $name
     * @param  Closure $closure
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function receive($name, Closure $closure)
    {
        $path = Arr::get($this->config, 'path');
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        try {
            $uploadedFile = $this->uploader
                ->setPath($path)
                ->receive($name);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }

        $response = $closure($uploadedFile);
        $file = $uploadedFile->getPathname();
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }
        $this->cleanDirectory($path);

        return $this->uploader->completedResponse($response);
    }

    /**
     * cleanDirectory.
     *
     * @param  string $path
     */
    protected function cleanDirectory($path)
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
