<?php

namespace Recca0120\Upload\Tests;

use Mockery as m;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Filesystem;
use org\bovigo\vfs\content\LargeFileContent;

class FilesystemTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testBaseName()
    {
        $filesystem = new Filesystem();
        $this->assertSame(
            basename(__FILE__, PATHINFO_BASENAME),
            $filesystem->basename($path = __FILE__)
        );
    }

    public function testTmpfilename()
    {
        $filesystem = new Filesystem();
        $path = __FILE__;
        $hash = uniqid();
        $this->assertSame(
            md5($path.$hash).'.php',
            $filesystem->tmpfilename($path, $hash)
        );
    }

    public function testAppendStream()
    {
        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')
            ->withContent(LargeFileContent::withKilobytes(10))
            ->at($root);
        $output = vfsStream::newFile('output.txt')
            ->at($root);
        $filesystem = new Filesystem();
        $offset = 0;
        $appendContent = '';
        for ($i = 0; $i < 5; $i++) {
            $filesystem->appendStream($output->url(), $input->url(), $offset);
            $appendContent .= $input->getContent();
            $offset += $input->size();
            $this->assertSame($offset, $output->size());
            $this->assertSame($appendContent, $output->getContent());
        }
    }

    /**
     * @expectedException Recca0120\Upload\Exceptions\ResourceOpenException
     * @expectedExceptionMessage Failed to open output stream.
     * @expectedExceptionCode 102
     */
    public function testOutputIsNotResource()
    {
        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')
            ->withContent(LargeFileContent::withKilobytes(10))
            ->at($root);
        $filesystem = new Filesystem();
        $offset = 0;
        $filesystem->appendStream(null, $input->url(), $offset);
    }

    /**
     * @expectedException Recca0120\Upload\Exceptions\ResourceOpenException
     * @expectedExceptionMessage Failed to open input stream.
     * @expectedExceptionCode 101
     */
    public function testInputIsNotResource()
    {
        $root = vfsStream::setup();
        $output = vfsStream::newFile('output.txt')
            ->at($root);
        $filesystem = new Filesystem();
        $offset = 0;
        $filesystem->appendStream($output->url(), null, $offset);
    }

    public function testCreateUploadedFile()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('file.txt')
            ->at($root);
        $filesystem = new Filesystem();
        $filesystem->createUploadedFile($file->url(), basename($file->url()));
    }
}
