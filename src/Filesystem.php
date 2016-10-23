<?php

namespace Recca0120\Upload;

use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Support\Arr;
use Recca0120\Upload\Exceptions\InvalidUploadException;

class Filesystem extends IlluminateFilesystem
{
    /**
     * appendStream.
     *
     * @method appendStream
     *
     * @param string|resource $resource
     * @param string|resource $path
     * @param int             $offset
     */
    public function updateStream($path, $resource, $config = [])
    {
        $offset = Arr::get($config, 'offset', 0);
        $mode = ($offset === 0) ? 'wb' : 'ab';

        $resourceStream = (is_resource($resource) === true) ? $resource : @fopen($resource, $mode);
        if (is_resource($resourceStream) === false) {
            throw new InvalidUploadException('Failed to open input stream.', 101);
        }

        $pathStream = (is_resource($path) === true) ? $path : @fopen($path, $mode);
        if (is_resource($pathStream) === false) {
            throw new InvalidUploadException('Failed to open output stream.', 102);
        }

        fseek($pathStream, $offset);
        while ($buffer = fread($resourceStream, 4096)) {
            fwrite($pathStream, $buffer);
        }

        @fclose($resourceStream);
        @fclose($pathStream);
    }
}
