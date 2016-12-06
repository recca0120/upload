<?php

use Mockery as m;
use org\bovigo\vfs\vfsStream;
use Recca0120\Upload\Filesystem;
use org\bovigo\vfs\content\LargeFileContent;

class FilesystemTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function test_append_stream()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')
            ->withContent(LargeFileContent::withKilobytes(10))
            ->at($root);
        $output = vfsStream::newFile('output.txt')
            ->at($root);
        $offset = 0;
        $appendContent = '';
        $length = 4096;
        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem;

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        for ($i = 0; $i < 5; $i++) {
            $filesystem->appendStream($output->url(), $input->url(), $offset);
            $offset += $input->size();
            $appendContent .= $input->getContent();
            $this->assertSame($offset, $output->size());
            $this->assertSame($appendContent, $output->getContent());
        }
    }

    /**
     * @expectedException Recca0120\Upload\Exceptions\ResourceOpenException
     * @expectedExceptionMessage Failed to open output stream.
     * @expectedExceptionCode 102
     */
    public function test_output_is_not_resource()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')
            ->at($root);
        $output = null;
        $offset = 0;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem;

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $filesystem->appendStream($output, $input->url(), $offset);
    }

    /**
     * @expectedException Recca0120\Upload\Exceptions\ResourceOpenException
     * @expectedExceptionMessage Failed to open input stream.
     * @expectedExceptionCode 101
     */
    public function test_input_is_not_resource()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $root = vfsStream::setup();
        $input = null;
        $output = vfsStream::newFile('output.txt')
            ->at($root);
        $offset = 0;

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $filesystem = new Filesystem;

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $filesystem->appendStream($output->url(), $input, $offset);
    }

    public function test_create_uploaded_file()
    {
        /*
        |------------------------------------------------------------
        | Arrange
        |------------------------------------------------------------
        */

        $root = vfsStream::setup();
        $file = vfsStream::newFile('file.txt')
            ->at($root);

        /*
        |------------------------------------------------------------
        | Act
        |------------------------------------------------------------
        */

        $path = $file->url();
        $filesystem = new Filesystem;
        $mimeType = $filesystem->mimeType($path);
        $size = $filesystem->size($path);
        $name = $filesystem->basename($path);
        $uploadedFile = $filesystem->createUploadedFile($path, $name, $mimeType, $size);

        /*
        |------------------------------------------------------------
        | Assert
        |------------------------------------------------------------
        */

        $instance = class_exists('Illuminate\Http\UploadedFile') === true ?
            'Illuminate\Http\UploadedFile' :
            'Symfony\Component\HttpFoundation\File\UploadedFile';

        $this->assertInstanceOf($instance, $uploadedFile);
    }
}
