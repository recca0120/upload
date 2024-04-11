<?php

namespace Recca0120\Upload\Tests\Drivers;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Drivers\Plupload;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Tests\TestCase;
use ReflectionException;

class PluploadTest extends TestCase
{
    /**
     * @var Plupload
     */
    private $api;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new Plupload($this->config, $this->request, $this->files);
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveUploadSingleFile(): void
    {
        $this->assertTrue($this->api->receive('foo')->isFile());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     * @throws ReflectionException
     */
    public function testReceiveChunkedFile(): void
    {
        $size = $this->uploadedFile->getSize();

        $this->chunkUpload(3, function ($offset, $chunkSize, $index, $totalCount) use ($size) {
            $this->request->headers->replace([
                'Content-Length' => $chunkSize,
            ]);
            $this->request->replace([
                'name' => $this->uploadedFile->getClientOriginalName(),
                'chunk' => $index,
                'chunks' => $totalCount,
            ]);

            try {
                $uploadedFile = $this->api->receive('foo');
                self::assertEquals($size, $uploadedFile->getSize());
            } catch (ChunkedResponseException $e) {
                self::assertStringMatchesFormat(
                    '{"jsonrpc":"2.0","result":false}',
                    $e->getMessage()
                );
            }
        });
    }

    public function testResponse(): void
    {
        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"jsonrpc":"2.0","result":{}}', $response->getContent());
    }
}
