<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Recca0120\Upload\Filesystem;
use ReflectionClass;
use ReflectionException;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Request
     */
    protected $request;

    protected $uploadedFile;

    protected $root;

    protected $config;

    protected $files;

    protected $uuid;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uuid = uniqid('upload-', true);

        $this->root = vfsStream::setup('root', null, [
            'chunks' => [],
            'storage' => [],
        ]);

        $this->config = [
            'chunks' => $this->root->url().'/chunks',
            'storage' => $this->root->url().'/storage',
        ];

        $this->uploadedFile = UploadedFile::fake()->image('test.png');

        $this->request = Request::createFromGlobals();
        $this->request->setMethod('POST');
        $this->request->files->replace(['foo' => $this->uploadedFile]);

        $this->files = new Filesystem();
    }

    /**
     * @throws ReflectionException
     */
    protected function chunkUpload(int $chunks, callable $callback): void
    {
        $content = $this->uploadedFile->getContent();
        $size = $this->uploadedFile->getSize();
        $chunkLength = ($size - ($size % $chunks)) / $chunks + ($size % $chunks);

        $offset = 0;
        $index = 0;
        do {
            $chunkSize = min($chunkLength, $size - $offset);
            $this->setRequestContent(substr($content, $offset, $chunkSize));
            $callback($offset, $chunkSize, $index, $chunks);
            $offset += $chunkLength;
            $index++;
        } while ($offset <= $size);
        $this->setRequestContent($content);
        self::assertEquals($chunks, $index);
    }

    /**
     * @throws ReflectionException
     */
    protected function setRequestContent(string $content): void
    {
        $reflectedClass = new ReflectionClass($this->request);
        $reflection = $reflectedClass->getProperty('content');
        $reflection->setAccessible(true);
        $reflection->setValue($this->request, $content);
        file_put_contents($this->uploadedFile->getRealPath(), $content);
    }
}
