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
        $contentDisposition = (string) $this->request->header('content-disposition');
        if (empty($contentDisposition) === true) {
            return $this->request->file($name);
        }

        [$start, $end, $total] = $this->parseContentRange();
        $originalName = $this->getOriginalName($contentDisposition);
        $mimeType = $this->getMimeType($originalName);
        $uuid = $this->request->get('token');
        $completed = $end >= $total - 1;

        $chunkFile = $this->createChunkFile($originalName, $mimeType, $uuid);
        $chunkFile->appendStream('php://input', $start);

        if ($completed !== true) {
            throw new ChunkedResponseException([
                'files' => [
                    'name' => $originalName,
                    'size' => $end,
                    'type' => $chunkFile->getMimeType(),
                ],
            ], ['X-Last-Known-Byte' => $end]);
        }

        return $chunkFile->createUploadedFile();
    }

    protected function getOriginalName(string $contentDisposition): string
    {
        $originalName = (string) $this->request->get('name');
        if (empty($originalName) === true) {
            [$originalName] = sscanf(
                $contentDisposition,
                'attachment; filename=%s'
            );
        }

        return preg_replace('/[\'"]/', '', $originalName);
    }

    protected function getMimeType(string $originalName): string
    {
        $mimeType = (string) $this->request->header('content-type');
        if (empty($mimeType) === true) {
            $mimeType = $this->files->mimeType($originalName);
        }

        return $mimeType;
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
}
