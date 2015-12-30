<?php

namespace Recca0120\Upload\Driver;

use Closure;
use File;
use Recca0120\Upload\UploadException;

class FileApi extends AjaxUpload
{
    protected function hasChunks()
    {
        return $this->request->header('content-range') !== null;
    }

    protected function handleChunks($name, Closure $handler)
    {
        $result = false;
        list($originalName) = sscanf($this->request->header('content-disposition'), 'attachment; filename=%s');
        list($startOffset, $endOffset, $total) = sscanf($this->request->header('content-range'), 'bytes %d-%d/%d');
        $mimeType = $this->request->header('content-type');
        $mode = ($startOffset === 0) ? 'wb' : 'ab';
        $partialName = $this->getPartialName($originalName);
        $this->appendData($partialName, 'php://input', $mode);
        if ($total === 0) {
            throw new UploadException('Failed to open input stream', 101);
        }
        if (File::size($partialName) == $total) {
            $result = $this->receiveHandler($handler, $partialName, $originalName, $mimeType, $total);
        }

        return $result;
    }
}
