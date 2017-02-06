<?php

namespace Recca0120\Upload\Apis;

use Illuminate\Http\Request;

class FileAPI extends Base
{
    /**
     * getOriginalName.
     *
     * @return string
     */
    protected function getOriginalName()
    {
        $originalName = $this->request->get('name');
        if (empty($originalName) === true) {
            list($originalName) = sscanf(
                $this->request->header('content-disposition'),
                'attachment; filename=%s'
            );
        }

        return $originalName;
    }

    /**
     * getMimeType.
     *
     * @param string $originalName
     *
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
     *
     * @throws \Recca0120\Upload\Exceptions\ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    protected function doReceive($inputName)
    {
        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === true) {
            return $this->request->file($inputName);
        }

        list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');

        return $this->receiveChunkedFile(
            $originalName = $this->getOriginalName(),
            'php://input',
            $start,
            $this->getMimeType($originalName),
            $end >= $total - 1,
            [
                'X-Last-Known-Byte' => $end,
            ]
        );
    }
}
