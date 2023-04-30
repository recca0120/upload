<?php

namespace Recca0120\Upload;

use Illuminate\Filesystem\Filesystem as IlluminateFilesystem;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use TypeError;
use ValueError;

class Filesystem extends IlluminateFilesystem
{
    public function basename($path): string
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    public function tmpfilename(string $path, string $hash = null): string
    {
        return md5($path.$hash).'.'.strtolower($this->extension($path));
    }

    /**
     * @throws ResourceOpenException
     */
    public function appendStream($output, $input, int $offset): void
    {
        $mode = ($offset === 0) ? 'wb' : 'ab';
        $output = $this->convertToResource($output, $mode, 'output');
        $input = $this->convertToResource($input, 'rb');

        fseek($output, $offset);
        while ($buffer = fread($input, 4096)) {
            fwrite($output, $buffer);
        }

        fclose($output);
        fclose($input);
    }

    public function createUploadedFile(string $path, string $originalName, string $mimeType)
    {
        $class = class_exists(UploadedFile::class) === true ? UploadedFile::class : SymfonyUploadedFile::class;

        return new $class($path, $originalName, $mimeType, UPLOAD_ERR_OK, true);
    }

    /**
     * convertToResource.
     *
     * @param  mixed  $resource
     * @return resource
     *
     * @throws ResourceOpenException
     */
    protected function convertToResource($resource, string $mode = 'wb', string $type = 'input')
    {
        try {
            $resource = is_resource($resource) === true ? $resource : @fopen($resource, $mode);
        } catch (TypeError|ValueError $error) {
        }

        if (is_resource($resource) === false) {
            $code = $type === 'input' ? 101 : 102;

            throw new ResourceOpenException('Failed to open '.$type.' stream.', $code);
        }

        return $resource;
    }
}
