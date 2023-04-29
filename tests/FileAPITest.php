<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\FileAPI;

class FileAPITest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FileAPI($this->config, $this->request, $this->files);
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveSingleFile(): void
    {
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $start = 0;
        $end = $this->uploadedFile->getSize();
        $total = $this->uploadedFile->getSize();

        $this->request->headers->replace([
            'content-disposition' => 'attachment; filename="'.$this->uploadedFile->getClientOriginalName().'"',
            'content-range' => "bytes {$start}-{$end}/${total}",
            'content-type' => 'image/png',
        ]);

        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileWithoutContentRange(): void
    {
        $this->request->headers->replace([
            'content-disposition' => 'attachment; filename="'.$this->uploadedFile->getClientOriginalName().'"',
            'content-length' => $this->uploadedFile->getSize(),
            'content-type' => 'image/png',
        ]);

        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileAndThrowChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('{"files":{"name":"test.png","size":10,"type":"image\/png"}}');

        $start = 0;
        $end = 10;
        $total = $this->uploadedFile->getSize();

        $this->request->headers->replace([
            'content-disposition' => 'attachment; filename="'.$this->uploadedFile->getClientOriginalName().'"',
            'content-range' => "bytes {$start}-{$end}/${total}",
            'content-type' => 'image/png',
        ]);

        $this->api->receive('foo');
    }
}
