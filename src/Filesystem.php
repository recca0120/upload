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
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * tmpfilename.
     *
     * @param string $path
     * @param string $hash
     * @return string
     */
    public function tmpfilename($path, $hash = null)
    {
        return md5($path.$hash).'.'.$this->extension($path);
    }

    /**
     * appendStream.
     *
     * @param string $output
     * @param string|resource $input
     * @param int $offset
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

        fclose($output);
        fclose($input);
    }

    /**
     * createUploadedFile.
     *
     * @param string $path
     * @param string $originalName
     * @param string $mimeType
     * @param int $size
     * @return \Illuminate\Http\UploadedFile
     */
    public function createUploadedFile($path, $originalName, $mimeType = null, $size = null)
    {
        $class = class_exists('Illuminate\Http\UploadedFile') === true ?
            'Illuminate\Http\UploadedFile' : 'Symfony\Component\HttpFoundation\File\UploadedFile';

        $mimeType = $mimeType ?: $this->mimeType($path);
        $size = $size ?: $this->size($path);

        return new $class($path, $originalName, $mimeType, $size, UPLOAD_ERR_OK, true);
    }

    /**
     * convertToResource.
     *
     * @param string|resource $resource
     * @param string $mode
     * @param string $type
     * @return resource
     */
    protected function convertToResource($resource, $mode = 'wb', $type = 'input')
    {
        $resource = is_resource($resource) === true ?
            $resource : @fopen($resource, $mode);

        if (is_resource($resource) === false) {
            $code = $type === 'input' ? 101 : 102;

            throw new ResourceOpenException('Failed to open '.$type.' stream.', $code);
        }

        return $resource;
    }
}
