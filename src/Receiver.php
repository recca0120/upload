<?php

namespace Recca0120\Upload;

use Closure;
use Recca0120\Upload\Contracts\Uploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
     * __construct.
     *
     * @param \Recca0120\Upload\Contracts\Uploader   $uploader
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(Uploader $uploader)
    {
        $this->uploader = $uploader;
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
        try {
            $uploadedFile = $this->uploader->receive($name);
            $response = $closure($uploadedFile);
            $this->deleteUploadedFile($uploadedFile);

            return $this->uploader->completedResponse($response);
        } catch (ChunkedResponseException $e) {
            return $e->getResponse();
        }
    }

    /**
     * deleteUploadedFile.
     *
     * @param  UploadedFile $uploadedFile
     */
    protected function deleteUploadedFile(UploadedFile $uploadedFile)
    {
        $filesystem = $this->uploader->getFilesystem();
        $file = $uploadedFile->getPathname();
        if ($filesystem->isFile($file) === true) {
            $filesystem->delete($file);
        }
    }
}
