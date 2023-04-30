<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class FilePond extends Api
{
    /**
     * @throws ResourceOpenException
     * @throws FileNotFoundException
     */
    public function receive(string $name)
    {
        if (! $this->isChunked($name)) {
            return $this->request->file($name);
        }

        if (! $this->request->headers->has('Upload-Name')) {
            throw new ChunkedResponseException(md5(uniqid('file-pond-', true)));
        }

        $originalName = $this->request->header('Upload-Name');
        $uuid = $this->request->get('patch');
        $offset = (int) $this->request->header('Upload-Offset');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($this->request->getContent(true), $offset);

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException('', [], 204);
        }

        return $chunkFile->createUploadedFile();
    }

    protected function isChunked(string $name): bool
    {
        return empty($this->request->file($name));
    }

    protected function isCompleted(string $name): bool
    {
        $offset = (int) $this->request->header('Upload-Offset');
        $size = (int) $this->request->header('Upload-Length');
        $length = (int) $this->request->header('Content-Length');

        return $size === $offset + $length;
    }
}
