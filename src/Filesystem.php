<?php

namespace Recca0120\Upload;

use Recca0120\Upload\Exceptions\ResourceOpenException;
use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;

class Filesystem extends IlluminateFilesystem
{
    /**
     * Extract the trailing name component from a file path.
     *
     * @param string $path
     *
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * appendStream.
     *
     * @param string|resource $output
     * @param string|resource $input
     * @param int             $offset
     */
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

    /**
     * convertToResource.
     *
     * @param string|resource $resource [description]
     * @param string          $mode     string
     * @param string          $type     string
     *
     * @return resource
     */
    protected function convertToResource($resource, $mode = 'wb', $type = 'input')
    {
        $resource = is_resource($resource) === true
            ? $resource
            : @fopen($resource, $mode);

        if (is_resource($resource) === false) {
            $code = $type === 'input' ? 101 : 102;

            throw new ResourceOpenException('Failed to open '.$type.' stream.', $code);
        }

        return $resource;
    }

    /**
     * createUploadedFile.
     *
     * @param string $path
     * @param string $originalName
     * @param string $mimeType
     * @param int    $size
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function createUploadedFile($path, $originalName, $mimeType = null, $size = null)
    {
        $class = class_exists('Illuminate\Http\UploadedFile') === true
            ? 'Illuminate\Http\UploadedFile'
            : 'Symfony\Component\HttpFoundation\File\UploadedFile';

        return new $class($path, $originalName, $mimeType, $size, UPLOAD_ERR_OK, true);
    }
}
