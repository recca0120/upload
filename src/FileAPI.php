<?php

namespace Recca0120\Upload;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class FileAPI extends Api
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

        $contentDisposition = (string) $this->request->header('content-disposition');
        [$start, $end, $total] = $this->parseContentRange();
        $originalName = $this->parseOriginalName($contentDisposition);
        $mimeType = $this->request->header('content-type');
        $uuid = $this->request->get('token');
        $completed = $end >= $total - 1;

        $chunkFile = $this->createChunkFile($originalName, $mimeType, $uuid);
        $chunkFile->appendStream($this->request->getContent(true), $start);

        if ($completed !== true) {
            throw new ChunkedResponseException([
                'files' => [
                    'name' => $originalName,
                    'size' => $end,
                    'type' => $mimeType,
                ],
            ], ['X-Last-Known-Byte' => $end]);
        }

        return $chunkFile->createUploadedFile();
    }

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
}
