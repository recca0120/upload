<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Plupload;

class PluploadTest extends TestCase
{
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
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"jsonrpc":"2.0","result":{}}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $this->request->replace(['chunk' => 0, 'chunks' => 1, 'name' => '']);
        $this->request->headers->replace(['content-length' => $this->uploadedFile->getSize()]);

        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"jsonrpc":"2.0","result":{}}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileAndThrowChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('{"jsonrpc":"2.0","result":false}');

        $this->request->replace(['chunk' => 0, 'chunks' => 2]);
        $this->request->headers->replace(['content-length' => $this->uploadedFile->getSize()]);

        self::assertTrue($this->api->receive('foo')->isValid());
    }
}
