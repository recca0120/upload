<?php

namespace Recca0120\Upload\Drivers;

use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class FileAPI extends Api
{
    protected function parseOriginalName(string $contentDisposition): string
    {
        $originalName = (string) $this->request->get('name');
        if (empty($originalName) === true) {
            [$originalName] = sscanf($contentDisposition, 'attachment; filename=%s');
        }

        return rawurldecode(preg_replace('/[\'"]/', '', $originalName));
    }

    protected function parseContentRange(): array
    {
        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === false) {
            [$start, $end, $total] = sscanf($contentRange, 'bytes %d-%d/%d');

            return [$start, $end, $total];
        }

        $total = $end = (int) $this->request->header('content-length');

        return [0, $end, $total];
    }

    protected function isChunked(string $name): bool
    {
        return ! empty($this->request->header('content-disposition'));
    }

    protected function isCompleted(string $name): bool
    {
        [, $end, $total] = $this->parseContentRange();

        return $end >= $total - 1;
    }

    protected function receiveChunked(string $name): UploadedFile
    {
        $contentDisposition = (string) $this->request->header('content-disposition');
        [$start, $end, $total] = $this->parseContentRange();
        $originalName = $this->parseOriginalName($contentDisposition);
        $mimeType = $this->request->header('content-type');
        $uuid = $this->request->get('token') ?? md5($originalName.$total);

        $chunkFile = $this->createChunkFile($originalName, $uuid, $mimeType);
        $chunkFile->appendStream($this->getResource($name), $start);

        if (! $this->isCompleted($name)) {
            $message = ['files' => ['name' => $originalName, 'size' => $end, 'type' => $mimeType]];

            throw new ChunkedResponseException($message, ['X-Last-Known-Byte' => $end]);
        }

        return $chunkFile->createUploadedFile();
    }

    private function getResource(string $name)
    {
        $resource = $this->request->file($name);

        if (! $resource) {
            $resource = $this->request->getContent(true);
        }

        return $resource;
    }
}
