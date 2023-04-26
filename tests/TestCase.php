<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Recca0120\Upload\Filesystem;

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
        $this->request->files->replace(['foo' => $this->uploadedFile]);

        $this->files = new Filesystem();
    }
}
