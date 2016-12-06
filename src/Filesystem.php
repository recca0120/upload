<?php

namespace Recca0120\Upload;

use Exception;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;

class Filesystem extends IlluminateFilesystem
{
    public function appendStream($output, $input, $offset = 0)
    {
        $mode = ($offset === 0) ? 'wb' : 'ab';
        $output = $this->convertToResource($output, $mode, 'output');
        $input = $this->convertToResource($input, 'rb', 'input');

        fseek($output, $offset);
        while ($buffer = fread($input, 4096)) {
            fwrite($output, $buffer);
        }

        @fclose($output);
        @fclose($input);
    }

    protected function convertToResource($resource, $mode = 'wb', $type = 'input')
    {
        $resource = is_resource($resource) === true ? $resource : @fopen($resource, $mode);

        if (is_resource($resource) === false) {
            $code = $type === 'input' ? 101 : 102;

            throw new Exception('Failed to open '.$type.' stream.', $code);
        }

        return $resource;
    }

    public function createUploadedFile($path, $originalName, $mimeType = null, $size = null)
    {
        $class = class_exists('Illuminate\Http\UploadedFile') === true ?
            'Illuminate\Http\UploadedFile' :
            'Symfony\Component\HttpFoundation\File\UploadedFile';

        return new $class($path, $originalName, $mimeType, $size, UPLOAD_ERR_OK, true);
    }
}
