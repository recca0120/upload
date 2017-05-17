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
        parent::tearDown();
        m::close();
    }

    public function testBaseName()
    {
        $files = new Filesystem();
        $this->assertSame(
            basename(__FILE__, PATHINFO_BASENAME),
            $files->basename($path = __FILE__)
        );
    }

    public function testTmpfilename()
    {
        $files = new Filesystem();
        $path = __FILE__;
        $hash = uniqid();
        $this->assertSame(
            md5($path.$hash).'.php',
            $files->tmpfilename($path, $hash)
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
        $files = new Filesystem();
        $offset = 0;
        $appendContent = '';
        for ($i = 0; $i < 5; $i++) {
            $files->appendStream($output->url(), $input->url(), $offset);
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
        $files = new Filesystem();
        $offset = 0;
        $files->appendStream(null, $input->url(), $offset);
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
        $files = new Filesystem();
        $offset = 0;
        $files->appendStream($output->url(), null, $offset);
    }

    public function testCreateUploadedFile()
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('file.txt')
            ->at($root);
        $files = new Filesystem();
        $this->assertInstanceOf(
            'Symfony\Component\HttpFoundation\File\UploadedFile',
            $files->createUploadedFile($file->url(), basename($file->url()))
        );
    }
}
