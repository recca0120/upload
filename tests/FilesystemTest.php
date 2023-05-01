<?php

namespace Recca0120\Upload\Tests;

use Illuminate\Http\UploadedFile;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\content\LargeFileContent;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Recca0120\Upload\Exceptions\ResourceOpenException;
use Recca0120\Upload\Filesystem;

class FilesystemTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBaseName(): void
    {
        $files = new Filesystem();

        $this->assertSame(basename(__FILE__, PATHINFO_BASENAME), $files->basename(__FILE__));
    }

    public function testTmpfilename(): void
    {
        $files = new Filesystem();
        $path = __FILE__;
        $hash = uniqid('', true);

        $this->assertSame(md5($path.$hash).'.php', $files->tmpfilename($path, $hash));
    }

    /**
     * @throws ResourceOpenException
     */
    public function testAppendStream(): void
    {
        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')->withContent(LargeFileContent::withKilobytes(10))->at($root);
        $output = vfsStream::newFile('output.txt')->at($root);

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

    public function testOutputIsNotResource(): void
    {
        $this->expectExceptionCode(102);
        $this->expectExceptionMessage('Failed to open output stream.');
        $this->expectException(ResourceOpenException::class);

        $root = vfsStream::setup();
        $input = vfsStream::newFile('input.txt')->withContent(LargeFileContent::withKilobytes(10))->at($root);
        $files = new Filesystem();
        $offset = 0;
        $files->appendStream(null, $input->url(), $offset);
    }

    public function testInputIsNotResource(): void
    {
        $this->expectExceptionCode(101);
        $this->expectExceptionMessage('Failed to open input stream.');
        $this->expectException(ResourceOpenException::class);

        $root = vfsStream::setup();
        $output = vfsStream::newFile('output.txt')->at($root);
        $files = new Filesystem();
        $offset = 0;
        $files->appendStream($output->url(), null, $offset);
    }

    public function testCreateUploadedFile(): void
    {
        $root = vfsStream::setup();
        $file = vfsStream::newFile('file.txt')->at($root);
        $files = new Filesystem();

        $this->assertInstanceOf(
            UploadedFile::class,
            $files->createUploadedFile($file->url(), basename($file->url()), $files->mimeType($file->url()))
        );
    }
}
