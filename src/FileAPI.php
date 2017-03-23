<?php

namespace Recca0120\Upload;

class FileAPI extends Api
{
    /**
     * getOriginalName.
     *
     * @return string
     */
    protected function getOriginalName($contentDisposition)
    {
        $originalName = $this->request->get('name');
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
        $mimeType = $this->request->header('content-type');
        if (empty($mimeType) === true) {
            $mimeType = $this->filesystem->mimeType($originalName);
        }

        return $mimeType;
    }

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
        $contentDisposition = $this->request->header('content-disposition');
        if (empty($contentDisposition) === true) {
            return $this->request->file($name);
        }

        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === false) {
            list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');
        } else {
            $start = 0;
            $end = $this->request->header('content-length');
            $total = $end;
        }

        $originalName = $this->getOriginalName($contentDisposition);
        $mimeType = $this->getMimeType($originalName);
        $input = 'php://input';
        $completed = $end >= $total - 1;
        $options = [
            'mimeType' => $mimeType,
            'message' => json_encode(['files' => [
                'name' => $originalName,
                'size' => $end,
                'type' => $mimeType,
            ]]),
            'headers' => [
                'X-Last-Known-Byte' => $end,
            ],
        ];

        return $this->receiveChunks($originalName, $input, $start, $completed, $options);
    }
}
