<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

interface Api
{
    public function domain(): string;

    public function path(): string;

    /**
     * @return self
     */
    public function makeDirectory(string $path);

    /**
     * @return self
     */
    public function cleanDirectory(string $path);

    /**
     * @return UploadedFile|SymfonyUploadedFile
     *
     * @throws ChunkedResponseException
     */
    public function receive(string $name);

    /**
     * @param  UploadedFile|SymfonyUploadedFile  $uploadedFile
     * @return self
     */
    public function deleteUploadedFile($uploadedFile);

    public function completedResponse(JsonResponse $response): JsonResponse;
}
