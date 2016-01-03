<?php

namespace Recca0120\Upload\Services;

use Closure;
use File;

class Plupload extends AjaxUpload
{
    protected function hasChunks()
    {
        return $this->request->get('chunks') !== null;
    }

    protected function handleChunks($name, Closure $handler)
    {
        $result = false;
        if (($file = $this->request->file($name)) !== null) {
            $originalName = $this->request->get('name');
            $mimeType = $file->getMimeType();
            $chunk = (int) $this->request->get('chunk', false);
            $chunks = (int) $this->request->get('chunks', false);
            $mode = ($chunk === 0) ? 'wb' : 'ab';
            $partialName = $this->getPartialName($originalName);
            $this->appendData($partialName, $file->getPathname(), $mode);
            if ($chunk == $chunks - 1) {
                $result = $this->receiveHandler($handler, $partialName, $originalName, $mimeType, File::size($partialName));
            }
        }

        return $result;
    }

    public function receive($name, Closure $handler)
    {
        return response()->json([
            'jsonrpc' => '2.0',
            'result'  => parent::receive($name, $handler)->getData(),
        ]);
    }
}
