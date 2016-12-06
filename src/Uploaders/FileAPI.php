<?php

namespace Recca0120\Upload\Uploaders;

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
            list($originalName) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');
        }

        return $originalName;
    }

    /**
     * getMimeType.
     *
     * @param  string $originalName
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
     * @param string $name
     *
     * @throws ChunkedResponseException
     *
     * @return \Symfony\Component\HttpFoundation\File\UploadedFile
     */
    public function receive($name)
    {
        $contentRange = $this->request->header('content-range');
        if (empty($contentRange) === true) {
            return $this->request->file($name);
        }

        list($start, $end, $total) = sscanf($contentRange, 'bytes %d-%d/%d');

        $originalName = $this->getOriginalName();
        $mimeType = $this->getMimeType($originalName);

        $isCompleted = ($end >= $total - 1);
        $input = 'php://input';
        return $this->receiveChunkedFile($originalName, $input, $start, $mimeType, $isCompleted, [
            'X-Last-Known-Byte' => $end,
        ]);
    }
}
