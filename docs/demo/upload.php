<?php

use Recca0120\Upload\Dropzone;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\FilePond;
use Recca0120\Upload\FineUploader;
use Recca0120\Upload\Plupload;
use Recca0120\Upload\Receiver;

if (file_exists(__DIR__.'/../../vendor/autoload.php')) {
    include __DIR__.'/../../vendor/autoload.php';
} else {
    include __DIR__.'/vendor/autoload.php';
}

$config = [
    'chunks' => 'temp/chunks',
    'storage' => 'temp',
    'domain' => 'http://app.dev/',
    'path' => 'temp',
];

$inputName = 'file';
$api = isset($_GET['api']) ? $_GET['api'] : null;

switch ($api) {
    case 'plupload':
        $receiver = new Receiver(new Plupload($config));
        break;

    case 'fine-uploader':
        $receiver = new Receiver(new FineUploader($config));
        break;

    case 'dropzone':
        $receiver = new Receiver(new Dropzone($config));
        break;

    case 'filepond':
        $receiver = new Receiver(new FilePond($config));
        break;

    default:
        $receiver = new Receiver(new FileAPI($config));
        break;
}

$receiver->receive($inputName)->send();
