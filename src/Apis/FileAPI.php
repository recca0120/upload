<?php

namespace Recca0120\Upload\Apis;

class FileAPI extends Base
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
     * @param string $inputName
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     */
    protected function doReceive($inputName)
    {
        $contentDisposition = $this->request->header('content-disposition');
        if (empty($contentDisposition) === true) {
            return $this->request->file($inputName);
        }

        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === false) {
            list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');
        } else {
            $start = 0;
            $end = $this->request->header('content-length');
            $total = $end;
        }

        return $this->receiveChunkedFile(
            $originalName = $this->getOriginalName($contentDisposition),
            'php://input',
            $start,
            $end >= $total - 1,
            ['mimeType' => $this->getMimeType($originalName), 'headers' => ['X-Last-Known-Byte' => $end]]
        );
    }
}
