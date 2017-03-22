<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Exceptions\ChunkedResponseException;

class FileAPITest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testReceiveSingleFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn('');
        $inputName = 'foo';
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(
            $uploadedFile = m::mock('Symfony\Component\HttpFoundation\File\UploadedFile')
        );
        $filesystem->shouldReceive('files')->once()->andReturn([]);
        $this->assertSame($uploadedFile, $api->receive($inputName));
    }

    public function testReceiveChunkedFile()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 7845180;
        $total = 7845180;
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->shouldReceive('header')->once()->with('content-range')->andReturn(
            $contentRange = 'bytes '.$start.'-'.$end.'/'.$total
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );
        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $filesystem->shouldReceive('tmpfilename')->once()->with($originalName, $token)->andReturn($tmpfilename = 'foo');
        $filesystem->shouldReceive('appendStream')->once()->with($chunksPath.$tmpfilename.'.part', 'php://input', $start);
        $filesystem->shouldReceive('move')->once()->with($chunksPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
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

        $api->receive($inputName = 'foo');
    }

    public function testReceiveChunkedFileWithoutContentRange()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $request->shouldReceive('header')->once()->with('content-range')->andReturn(null);
        $request->shouldReceive('header')->once()->with('content-length')->andReturn($total = 7845180);
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename="'.($originalName = 'foo.php').'"'
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );
        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $filesystem->shouldReceive('tmpfilename')->once()->with($originalName, $token)->andReturn($tmpfilename = 'foo');
        $filesystem->shouldReceive('appendStream')->once()->with($chunksPath.$tmpfilename.'.part', 'php://input', 0);
        $filesystem->shouldReceive('move')->once()->with($chunksPath.$tmpfilename.'.part', $storagePath.$tmpfilename);
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

        $api->receive($inputName = 'foo');
    }

    public function testReceiveChunkedFileAndThrowChunkedResponseException()
    {
        $request = m::mock('Illuminate\Http\Request');
        $request->shouldReceive('root')->once()->andReturn($root = 'root');
        $api = new FileAPI(
            $config = ['chunks' => $chunksPath = 'foo/', 'storage' => $storagePath = 'foo/'],
            $request,
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $filesystem->shouldReceive('isDirectory')->twice()->andReturn(true);

        $start = 5242880;
        $end = 5767167;
        $total = 7845180;
        $request->shouldReceive('header')->once()->with('content-range')->andReturn(
            $contentRange = 'bytes '.$start.'-'.$end.'/'.$total
        );
        $request->shouldReceive('get')->once()->with('name')->andReturn('');
        $request->shouldReceive('header')->once()->with('content-disposition')->andReturn(
            'attachment; filename='.($originalName = 'foo.php')
        );
        $request->shouldReceive('header')->once()->with('content-type')->andReturn(
            $mimeType = 'foo'
        );

        $request->shouldReceive('get')->once()->with('token')->andReturn($token = 'foo');
        $filesystem->shouldReceive('tmpfilename')->once()->with($originalName, $token)->andReturn($tmpfilename = 'foo');
        $filesystem->shouldReceive('appendStream')->once()->with($chunksPath.$tmpfilename.'.part', 'php://input', $start);

        try {
            $api->receive($inputName = 'foo');
        } catch (ChunkedResponseException $e) {
            $response = $e->getResponse();
            $this->assertSame(201, $response->getStatusCode());
            $this->assertSame($end, $response->headers->get('X-Last-Known-Byte'));
        }
    }
}
