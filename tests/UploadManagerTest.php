<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\UploadManager;

class UploadManagerTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testCreateDefaultDriver()
    {
        $uploadManager = new UploadManager(
            [
                'config' => [
                    'upload' => [],
                ],
            ],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $request->shouldReceive('root')->andReturn($root = 'foo');
        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver());
    }

    public function testCreatePluploadDriver()
    {
        $uploadManager = new UploadManager(
            [
                'config' => [
                    'upload' => [],
                ],
            ],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $request->shouldReceive('root')->andReturn($root = 'foo');
        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver('plupload'));
    }

    public function testCreateFineUploaderDriver()
    {
        $uploadManager = new UploadManager(
            [
                'config' => [
                    'upload' => [],
                ],
            ],
            $request = m::mock('Illuminate\Http\Request'),
            $filesystem = m::mock('Recca0120\Upload\Filesystem')
        );
        $request->shouldReceive('root')->andReturn($root = 'foo');
        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver('fine-uploader'));
    }
}
