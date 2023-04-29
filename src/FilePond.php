<?php

namespace Recca0120\Upload;

class FilePond extends Api
{
    public function receive(string $name)
    {
        $uploadedFile = $this->request->file($name);
        if (! empty($uploadedFile)) {
            return $uploadedFile;
        }
    }
}
