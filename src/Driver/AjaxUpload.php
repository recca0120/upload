<?php

namespace Recca0120\Upload\Driver;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Recca0120\Upload\UploadException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

abstract class AjaxUpload
{
    /**
     * request.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * file age.
     *
     * @var int
     */
    protected $maxFileAge = 600;

    /**
     * construct.
     *
     * @param \Illuminate\Http\Request          $request
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Request $request, Filesystem $filesystem)
    {
        $this->request = $request;
        $this->filesystem = $filesystem;
    }

    /**
     * has chunks.
     *
     * @return bool
     */
    abstract protected function hasChunks();

    /**
     * handle chunks.
     *
     * @param string  $name
     * @param Closure $handler
     *
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    abstract protected function handleChunks($name, Closure $handler);

    /**
     * receive.
     *
     * @param string  $name
     * @param Closure $handler
     *
     * @return mixed
     */
    public function receive($name, Closure $handler)
    {
        if ($this->hasChunks() === true) {
            $response = $this->handleChunks($name, $handler);
        } else {
            $response = $this->handleSingle($name, $handler);
        }

        if ($this->isResponse($response) === true) {
            return $response;
        }

        return response()->json($response);
    }

    /**
     * handle single file.
     *
     * @param string  $name    [description]
     * @param Closure $handler [description]
     *
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function handleSingle($name, Closure $handler)
    {
        if ($this->request->file($name)) {
            return $handler($this->request->file($name));
        }
    }

    /**
     * chunk path.
     *
     * @return string
     */
    protected function chunkPath()
    {
        $path = storage_path('chunkupload');
        if ($this->filesystem->isDirectory($path) === false) {
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }

        return $path;
    }

    /**
     * chunk partial name.
     *
     * @param  string
     *
     * @return string
     */
    protected function getPartialName($filename)
    {
        $extension = null;
        if (($pos = strrpos($filename, '.')) !== -1) {
            $extension = '.'.substr($filename, $pos + 1);
        }

        return $this->chunkPath().'/'.md5($filename).$extension.'.part';
    }

    /**
     * append data.
     *
     * @param string $output
     * @param string $input
     * @param float  $mode
     * @param float  $offset
     *
     * @return void
     */
    protected function appendData($output, $input, $mode, $offset = null)
    {
        $this->removeOldData($this->chunkPath());

        if (!($out = @fopen($output, $mode))) {
            throw new UploadException('Failed to open output stream.', 102);
        }

        if (!($in = @fopen($input, 'rb'))) {
            throw new UploadException('Failed to open input stream', 101);
        }

        if ($offset !== null) {
            fseek($out, $offset);
        }

        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }

        @fclose($out);
        @fclose($in);
    }

    /**
     * remove old data.
     *
     * @param string $path
     *
     * @return void
     */
    public function removeOldData($path)
    {
        $time = time();
        foreach ($this->filesystem->files($path) as $file) {
            if ($this->filesystem->lastModified($file) < ($time - $this->maxFileAge)) {
                $this->filesystem->delete($file);
            }
        }
    }

    /**
     * receive handler.
     *
     * @param Closure $handler
     * @param string  $partialName
     * @param string  $originalName
     * @param string  $mimeType
     * @param int     $fileSize
     *
     * @return Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function receiveHandler(Closure $handler, $partialName, $originalName, $mimeType, $fileSize = null)
    {
        $file = new UploadedFile($partialName, $originalName, $mimeType, $fileSize, UPLOAD_ERR_OK, true);
        $result = $handler($file);
        $this->filesystem->delete($partialName);

        return $result;
    }

    /**
     * is response.
     *
     * @param mixed $response
     *
     * @return bool
     */
    protected function isResponse($response)
    {
        return $response instanceof Response;
    }
}
