<?php

namespace Recca0120\Upload\Tests\Apis;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Apis\Base;

class BaseTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testGetConfig()
    {
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $this->assertSame($config, $api->getConfig());
        $this->assertSame($chunksPath, $api->getChunksPath());
    }

    public function testMakeDirectory()
    {
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $path = __DIR__;
        $filesystem->shouldReceive('isDirectory')->once()->with($path)->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->once()->with($path, 0777, true, true)->andReturn(false);
        $api->makeDirectory($path);
    }

    public function testCleanDirectory()
    {
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $path = __DIR__;
        $filesystem->shouldReceive('files')->once()->with($path)->andReturn([$file = __FILE__]);
        $filesystem->shouldReceive('isFile')->once()->with($file)->andReturn(true);
        $filesystem->shouldReceive('lastModified')->once()->with($file)->andReturn(time() - 86400);
        $filesystem->shouldReceive('delete')->once()->with($file);
        $api->cleanDirectory($path);
    }

    public function testDeleteUploadedFile()
    {
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($file = __FILE__);
        $filesystem->shouldReceive('isFile')->once()->with($file)->andReturn(true);
        $filesystem->shouldReceive('delete')->once()->with($file);
        $api->deleteUploadedFile($uploadedFile);
    }

    public function testCompletedResponse()
    {
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $api->completedResponse(
            $respomse = m::mock('Illuminate\Http\JsonResponse')
        );
    }
}

class Api extends Base
{
    protected function doReceive($inputName)
    {
    }
}
