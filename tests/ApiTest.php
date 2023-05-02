<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Api as ApiBase;
use Recca0120\Upload\Filesystem;

class ApiTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDomain(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn(null);

        $api = new Api(['domain' => $domain = 'foo/'], $request, m::mock(Filesystem::class));

        $this->assertSame($domain, $api->domain());
    }

    public function testPath(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $api = new Api(['path' => $path = 'foo/'], $request, m::mock(Filesystem::class));

        $this->assertSame($path, $api->path());
    }

    public function testMakeDirectory(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $files = m::mock(Filesystem::class);
        $path = __DIR__;
        $files->allows('isDirectory')->once()->with($path)->andReturn(false);
        $files->allows('makeDirectory')->once()->with($path, 0777, true, true)->andReturn(false);

        $api = new Api(['chunks' => 'foo/'], $request, $files);

        $this->assertSame($api, $api->makeDirectory($path));
    }

    public function testCleanDirectory(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $files = m::mock(Filesystem::class);
        $path = __DIR__;
        $files->allows('files')->once()->with($path)->andReturn([$file = __FILE__]);
        $files->allows('isFile')->once()->with($file)->andReturn(true);
        $files->allows('lastModified')->once()->with($file)->andReturn(time() - 86400);
        $files->allows('delete')->once()->with($file);

        $api = new Api(['chunks' => 'foo/'], $request, $files);

        $this->assertSame($api, $api->cleanDirectory($path));
    }

    public function testCompletedResponse(): void
    {
        $request = m::mock(Request::class);
        $request->allows('root')->once()->andReturn('root');

        $api = new Api(['chunks' => 'foo/'], $request, m::mock(Filesystem::class));

        $response = m::mock(JsonResponse::class);

        $this->assertSame($response, $api->completedResponse($response));
    }
}

class Api extends ApiBase
{
    public function receive(string $name): UploadedFile
    {
        return m::mock(UploadedFile::class);
    }

    protected function isChunked(string $name): bool
    {
        return false;
    }

    protected function isCompleted(string $name): bool
    {
        return true;
    }

    protected function receiveChunked(string $name): UploadedFile
    {
        return UploadedFile::fake()->create('test.png');
    }
}
