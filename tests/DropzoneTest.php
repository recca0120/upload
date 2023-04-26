<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Dropzone;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;

class DropzoneTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new Dropzone(
            $this->config,
            $this->request,
            $this->files,
            new ChunkFileFactory($this->files)
        );
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveSingleFile(): void
    {
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":null}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $this->request->replace([
            'dztotalchunkcount' => 1,
            'dzchunkindex' => 0,
            'dzuuid' => $this->uuid,
        ]);

        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"success":true,"uuid":"'.$this->uuid.'"}', $response->getContent());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileWithParts(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('{"success":true,"uuid":"'.$this->uuid.'"}');

        $this->request->replace([
            'dztotalchunkcount' => 2,
            'dzchunkindex' => 0,
            'dzuuid' => $this->uuid,
        ]);

        $this->api->receive('foo');
    }
}
