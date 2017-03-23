<?php

namespace Recca0120\Upload;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Contracts\Api as ApiContract;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

abstract class Api implements ApiContract
{
    /**
     * TMPFILE_EXTENSION.
     *
     * @var string
     */
    const TMPFILE_EXTENSION = '.part';

    /**
     * $request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

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
     * @param array $config
     * @param \Illuminate\Http\Request $request
     * @param \Recca0120\Upload\Filesystem $filesystem
     */
    public function __construct($config = [], Request $request = null, Filesystem $filesystem = null)
    {
        $this->request = $request ?: Request::capture();
        $this->filesystem = $filesystem ?: new Filesystem();
        $this->config = array_merge([
            'chunks' => sys_get_temp_dir().'/chunks',
            'storage' => 'storage/temp',
            'domain' => $this->request->root(),
            'path' => 'storage/temp',
        ], $config);
    }

    /**
     * chunkFile.
     *
     * @param string $tmpfilename
     * @return string
     */
    protected function chunkFile($tmpfilename)
    {
        $this->makeDirectory($this->config['chunks']);

        return rtrim($this->config['chunks'], '/').'/'.$tmpfilename.static::TMPFILE_EXTENSION;
    }

    /**
     * storageFile.
     *
     * @param string $tmpfilename
     * @return string
     */
    protected function storageFile($tmpfilename)
    {
        $this->makeDirectory($this->config['storage']);

        return rtrim($this->config['storage'], '/').'/'.$tmpfilename;
    }

    /**
     * domain.
     *
     * @return string
     */
    public function domain()
    {
        return rtrim($this->config['domain'], '/').'/';
    }

    /**
     * path.
     *
     * @return string
     */
    public function path()
    {
        return rtrim($this->config['path'], '/').'/';
    }

    /**
     * makeDirectory.
     *
     * @return $this
     */
    public function makeDirectory($path)
    {
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0777, true, true);
        }

        return $this;
    }

    /**
     * cleanDirectory.
     */
    public function cleanDirectory($path)
    {
        $time = time();
        $maxFileAge = 3600;
        $files = (array) $this->filesystem->files($path);
        foreach ($files as $file) {
            if ($this->filesystem->isFile($file) === true &&
                $this->filesystem->lastModified($file) < ($time - $maxFileAge)
            ) {
                $this->filesystem->delete($file);
            }
        }
    }

    /**
     * receiveChunks.
     *
     * @param string $originalName
     * @param string|resource $input
     * @param int $start
     * @param bool $completed
     * @param array $options
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    protected function receiveChunks($originalName, $input, $start, $completed = false, $options = [])
    {
        $tmpfilename = $this->filesystem->tmpfilename(
            $originalName, $this->request->get('token')
        );
        $chunkFile = $this->chunkFile($tmpfilename);
        $this->filesystem->appendStream($chunkFile, $input, $start);

        if ($completed === false) {
            throw new ChunkedResponseException(
                empty($options['message']) === false ? $options['message'] : '',
                empty($options['headers']) === false ? $options['headers'] : []
            );
        }

        $this->filesystem->move(
            $chunkFile, $storageFile = $this->storageFile($tmpfilename)
        );

        return $this->filesystem->createUploadedFile(
            $storageFile,
            $originalName,
            empty($options['mimeType']) === false ? $options['mimeType'] : $this->filesystem->mimeType($originalName),
            $this->filesystem->size($storageFile)
        );
    }

    /**
     * receive.
     *
     * @param string $inputName
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    abstract public function receive($inputName);

    /**
     * deleteUploadedFile.
     *
     * @param \Symfony\Component\HttpFoundation\File\UploadedFile
     * @return $this
     */
    public function deleteUploadedFile(UploadedFile $uploadedFile)
    {
        $file = $uploadedFile->getPathname();
        if ($this->filesystem->isFile($file) === true) {
            $this->filesystem->delete($file);
        }
        $this->cleanDirectory($this->config['chunks']);

        return $this;
    }

    /**
     * completedResponse.
     *
     * @param \Illuminate\Http\JsonResponse $response
     * @return \Illuminate\Http\JsonResponse
     */
    public function completedResponse(JsonResponse $response)
    {
        return $response;
    }
}
