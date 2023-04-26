<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\ChunkFileFactory;
use Recca0120\Upload\Exceptions\ChunkedResponseException;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;
use Recca0120\Upload\Plupload;

class PluploadTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $request;

    private $uploadedFile;

    protected function setUp(): void
    {
        parent::setUp();
        $root = vfsStream::setup('root', null, [
            'chunks' => [],
            'storage' => [],
        ]);

        $this->uploadedFile = UploadedFile::fake()->image('test.png');

        $this->request = Request::createFromGlobals();
        $this->request->files->replace(['foo' => $this->uploadedFile]);

        $config = ['chunks' => $root->url().'/chunks', 'storage' => $root->url().'/storage'];

        $this->api = new Plupload(
            $config,
            $this->request,
            new Filesystem(),
            new ChunkFileFactory(new Filesystem())
        );
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveUploadSingleFile(): void
    {
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFile(): void
    {
        $this->request->replace(['chunk' => 0, 'chunks' => 1, 'name' => '']);
        $this->request->headers->replace(['content-length' => $this->uploadedFile->getSize()]);

        $uploadedFile = $this->api->receive('foo');

        self::assertEquals($this->uploadedFile->getSize(), $uploadedFile->getSize());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testReceiveChunkedFileAndThrowChunkedResponseException(): void
    {
        $this->expectException(ChunkedResponseException::class);
        $this->expectExceptionMessage('');

        $this->request->replace(['chunk' => 0, 'chunks' => 2]);
        $this->request->headers->replace(['content-length' => $this->uploadedFile->getSize()]);

        self::assertTrue($this->api->receive('foo')->isValid());
    }

    /**
     * @throws FileNotFoundException
     * @throws ResourceOpenException
     */
    public function testCompletedResponse()
    {
        self::assertTrue($this->api->receive('foo')->isValid());

        $response = $this->api->completedResponse(new JsonResponse());

        self::assertEquals('{"jsonrpc":"2.0","result":{}}', $response->getContent());
    }
}
