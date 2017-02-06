<?php

namespace Recca0120\Upload\Tests\Apis;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Apis\Plupload;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class PluploadTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testReceiveUploadSingleFile()
    {
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);
        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn('');
        $filesystem->shouldReceive('files')->once()->andReturn([]);
        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);

        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn($chunks = 8);
        $request->shouldReceive('get')->once()->with('chunk')->andReturn($chunk = 8);
        $request->shouldReceive('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($pathname = 'foo');
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($contentLength = 1049073);
        $uploadedFile->shouldReceive('getMimeType')->once()->andReturn($mimeType = 'foo');

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $filesystem->shouldReceive('tmpfilename')->once()->with($originalName, $token)->andReturn($tmpfilename = 'foo');
        $filesystem->shouldReceive('appendStream')->once()->with($chunksPath.$tmpfilename.'.part', $pathname, $chunk * $contentLength);

        $filesystem->shouldReceive('move')->once()->with($chunksPath.$tmpfilename.'.part', $chunksPath.$tmpfilename);
        $filesystem->shouldReceive('size')->once()->with($chunksPath.$tmpfilename)->andReturn($size = 1024);
        $filesystem->shouldReceive('createUploadedFile')->once()->with(
            $chunksPath.$tmpfilename,
            $originalName,
            $mimeType,
            $size
        )->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $filesystem->shouldReceive('files')->once()->andReturn([]);

        $api->receive($inputName);
    }

    public function testReceiveChunkedFileAndThrowChunkedResponseException()
    {
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->once()->andReturn(true);

        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $request->shouldReceive('get')->once()->with('chunks')->andReturn($chunks = 8);
        $request->shouldReceive('get')->once()->with('chunk')->andReturn($chunk = 6);
        $request->shouldReceive('get')->once()->with('name')->andReturn($originalName = 'foo.php');
        $uploadedFile->shouldReceive('getPathname')->once()->andReturn($pathname = 'foo');
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($contentLength = 1049073);
        $uploadedFile->shouldReceive('getMimeType')->once()->andReturn($mimeType = 'foo');

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $filesystem->shouldReceive('tmpfilename')->once()->with($originalName, $token)->andReturn($tmpfilename = 'foo');
        $filesystem->shouldReceive('appendStream')->once()->with($chunksPath.$tmpfilename.'.part', $pathname, $chunk * $contentLength);

        try {
            $api->receive($inputName);
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
        }
    }

    public function testCompletedResponse()
    {
        $api = new Plupload(
            $config = ['chunks' => $chunksPath = 'foo/'],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $response = m::mock('Illuminate\Http\JsonResponse');
        $response->shouldReceive('getData')->once()->andReturn($data = []);
        $response->shouldReceive('setData')->once()->with([
            'jsonrpc' => '2.0',
            'result' => $data,
        ])->andReturnSelf();

        $this->assertSame($response, $api->completedResponse($response));
    }
}
