<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Api as ApiBase;

class ApiTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testDomain()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn(null);
        $api = new Api(
            $config = ['domain' => $domain = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $this->assertSame($domain, $api->domain());
    }

    public function testPath()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Api(
            $config = ['path' => $path = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $this->assertSame($path, $api->path());
    }

    public function testMakeDirectory()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $path = __DIR__;
        $filesystem->shouldReceive('isDirectory')->once()->with($path)->andReturn(false);
        $filesystem->shouldReceive('makeDirectory')->once()->with($path, 0777, true, true)->andReturn(false);
        $api->makeDirectory($path);
    }

    public function testCleanDirectory()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request,
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
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($file = __FILE__);
        $filesystem->shouldReceive('isFile')->once()->with($file)->andReturn(true);
        $filesystem->shouldReceive('delete')->once()->with($file);
        $filesystem->shouldReceive('files')->once()->andReturn([]);
        $api->deleteUploadedFile($uploadedFile);
    }

    public function testCompletedResponse()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new Api(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $api->completedResponse(
            $respomse = m::mock('Illuminate\Http\JsonResponse')
        );
    }
}

class Api extends ApiBase
{
    public function receive($inputName)
    {
    }
}
