<?php

namespace Recca0120\Upload\Tests;

use Recca0120\Upload\FilePond;

class FilePondTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->api = new FilePond($this->config, $this->request, $this->files);
    }

    public function testReceiveSingleFile(): void
    {
        $this->assertSame($this->uploadedFile, $this->api->receive('foo'));
    }
}
