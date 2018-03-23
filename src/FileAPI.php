<?php

namespace Recca0120\Upload;

class FileAPI extends Api
{
    /**
     * receive.
     *
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    public function receive($name)
    {
        $contentDisposition = (string) $this->request->header('content-disposition');
        if (empty($contentDisposition) === true) {
            return $this->request->file($name);
        }

        list($start, $end, $total) = $this->parseContentRange();
        $originalName = $this->getOriginalName($contentDisposition);
        $mimeType = $this->getMimeType($originalName);
        $uuid = $this->request->get('token');
        $completed = $end >= $total - 1;

        $chunkFile = $this->createChunkFile($originalName, $mimeType, $uuid);
        $chunkFile->appendStream('php://input', $start);

        return $completed === true
            ? $chunkFile->createUploadedFile()
            : $chunkFile->throwException([
                'files' => [
                    'name' => $originalName,
                    'size' => $end,
                    'type' => $chunkFile->getMimeType(),
                ],
            ], ['X-Last-Known-Byte' => $end]);
    }

    /**
     * getOriginalName.
     *
     * @param string $contentDisposition
     * @return string
     */
    protected function getOriginalName($contentDisposition)
    {
        $originalName = (string) $this->request->get('name');
        if (empty($originalName) === true) {
            list($originalName) = sscanf(
                $contentDisposition,
                'attachment; filename=%s'
            );
        }

        return preg_replace('/[\'"]/', '', $originalName);
    }

    /**
     * getMimeType.
     *
     * @param string $originalName
     * @return string
     */
    protected function getMimeType($originalName)
    {
        $mimeType = (string) $this->request->header('content-type');
        if (empty($mimeType) === true) {
            $mimeType = $this->files->mimeType($originalName);
        }

        return $mimeType;
    }

    /**
     * parseContentRange.
     *
     * @return array
     */
    protected function parseContentRange()
    {
        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === false) {
            return sscanf($contentRange, 'bytes %d-%d/%d');
        }

        $total = $end = (int) $this->request->header('content-length');

        return [0, $end, $total];
    }
}
