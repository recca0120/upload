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
        $uploadedFile = $this->request->file($name);
        if (! empty($uploadedFile)) {
            return $uploadedFile;
        }

        if (! $this->request->headers->has('Upload-Name')) {
            throw new ChunkedResponseException(md5(uniqid('file-pond-', true)));
        }

        $originalName = $this->request->header('Upload-Name');
        $uuid = $this->request->get('patch');
        $offset = (int) $this->request->header('Upload-Offset');

        $chunkFile = $this->createChunkFile($originalName, $uuid);
        $chunkFile->appendStream($this->request->getContent(true), $offset);

        if (! $this->completed($offset)) {
            throw new ChunkedResponseException('', [], 204);
        }

        return $chunkFile->createUploadedFile();
    }

    private function completed(int $offset): bool
    {
        $size = (int) $this->request->header('Upload-Length');
        $length = (int) $this->request->header('Content-Length');

        return $size === $offset + $length;
    }
}
