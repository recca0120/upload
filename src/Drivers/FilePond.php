<?php

namespace Recca0120\Upload\Drivers;

use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class FilePond extends Api
{
    protected function isChunked(string $name): bool
    {
        return empty($this->request->file($name));
    }

    protected function isCompleted(string $name): bool
    {
        $offset = $this->offset();
        $size = (int) $this->request->header('Upload-Length');
        $length = (int) $this->request->header('Content-Length');

        return $offset + $length >= $size;
    }

    protected function receiveChunked(string $name): UploadedFile
    {
        if (! $this->request->headers->has('Upload-Name')) {
            throw new ChunkedResponseException(md5(uniqid('file-pond-', true)));
        }

        $chunkFile = $this->createChunkFile(
            $this->request->header('Upload-Name'),
            $this->request->get('patch')
        );

        $chunkFile->appendStream(
            $this->request->getContent(true),
            $this->offset()
        );

        if (! $this->isCompleted($name)) {
            throw new ChunkedResponseException('', [], 204);
        }

        return $chunkFile->createUploadedFile();
    }

    private function offset(): int
    {
        return (int) $this->request->header('Upload-Offset');
    }
}
