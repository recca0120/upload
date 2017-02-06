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
            m::mock('Illuminate\Http\Request'),
            m::mock('Recca0120\Upload\Filesystem')
        );
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
            m::mock('Illuminate\Http\Request'),
            m::mock('Recca0120\Upload\Filesystem')
        );
        $this->assertInstanceOf('Recca0120\Upload\Receiver', $uploadManager->driver('plupload'));
    }
}
