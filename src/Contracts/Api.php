<?php

namespace Recca0120\Upload\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

interface Api
{
    public function domain(): string;

    public function path(): string;

    public function makeDirectory(string $path): Api;

    public function cleanDirectory(string $path): Api;

    /**
     * @throws ChunkedResponseException
     */
    public function receive(string $name): UploadedFile;

    public function clearTempDirectories(): Api;

    public function completedResponse(JsonResponse $response): JsonResponse;
}
