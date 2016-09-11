<?php

namespace Recca0120\Upload;

use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Recca0120\Upload\Exceptions\InvalidSanitizerException;

class Filesystem extends IlluminateFilesystem
{
    /**
     * appendStream.
     *
     * @method appendStream
     *
     * @param string|resource $source
     * @param string|resource $target
     * @param int             $offset
     */
    public function appendStream($source, $target, $offset = 0)
    {
        $mode = ($offset === 0) ? 'wb' : 'ab';

        $sourceStream = (is_resource($source) === true) ? $source : @fopen($source, $mode);
        if (is_resource($sourceStream) === false) {
            throw new InvalidSanitizerException('Failed to open input stream', 101);
        }

        $targetStream = (is_resource($target) === true) ? $target : @fopen($target, $mode);
        if (is_resource($targetStream) === false) {
            throw new InvalidSanitizerException('Failed to open output stream.', 102);
        }

        if (is_null($offset) === false) {
            fseek($targetStream, $offset);
        }

        while ($buffer = fread($sourceStream, 4096)) {
            fwrite($targetStream, $buffer);
        }

        @fclose($sourceStream);
        @fclose($targetStream);
    }
}
