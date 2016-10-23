<?php

use Mockery as m;
use Recca0120\Upload\Filesystem;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @expectedException \Recca0120\Upload\Exceptions\InvalidUploadException
     * @expectedExceptionMessage Failed to open input stream.
     * @expectedExceptionCode 101
     */
    public function test_source_isnot_resource()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $filesystem->updateStream(null, null, 0);
    }

    /**
     * @expectedException \Recca0120\Upload\Exceptions\InvalidUploadException
     * @expectedExceptionMessage Failed to open output stream.
     * @expectedExceptionCode 102
     */
    public function test_target_isnot_resource()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem();

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $filesystem->updateStream(null, fopen('php://input', 'w+'), 0);
    }

    public function test_write()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem();
        $source = tempnam(sys_get_temp_dir(), 'upload');
        $target = tempnam(sys_get_temp_dir(), 'upload');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        file_put_contents($source, 'writing to tempfile');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $filesystem->updateStream($target, $source, 0);
        // $this->assertSame('writing to tempfile', file_get_contents($target));
        unlink($source);
        unlink($target);
    }

    public function test_write_offset()
    {
        /*
        |------------------------------------------------------------
        | Set
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem();
        $source = tempnam(sys_get_temp_dir(), 'upload');
        $target = tempnam(sys_get_temp_dir(), 'upload');

        /*
        |------------------------------------------------------------
        | Expectation
        |------------------------------------------------------------
        */

        file_put_contents($source, 'writing to tempfile');

        /*
        |------------------------------------------------------------
        | Assertion
        |------------------------------------------------------------
        */

        $filesystem->updateStream($target, $source, 3);
        // $this->assertSame('writing to tempfile', file_get_contents($target));
        unlink($source);
        unlink($target);
    }
}
