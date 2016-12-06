<?php

namespace Recca0120\Upload\Uploader;

use Illuminate\Http\Request;

class FileAPI extends Uploader
{
    protected function getOriginalName()
    {
        $originalName = $this->request->get('name');
        if (empty($originalName) === true) {
            list($originalName) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');
        }

        return $originalName;
    }

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
    public function get($name)
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
        $tmpfile = $this->receive($originalName, $input, $start, $isCompleted, [
            'X-Last-Known-Byte' => $end,
        ]);
        $size = $this->filesystem->size($tmpfile);

        return $this->filesystem->createUploadedFile($tmpfile, $originalName, $mimeType, $size);
    }
}
