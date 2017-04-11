<?php

use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Plupload;
use Recca0120\Upload\Receiver;
use Recca0120\Upload\FineUploader;

include __DIR__.'/vendor/autoload.php';

$config = [
    'chunks' => 'temp/chunks',
    'storage' => 'temp',
    'domain' => 'http://app.dev/',
    'path' => 'temp',
];

$inputName = 'file';
$api = $_GET['api'];

switch ($api) {
    case 'plupload':
        $receiver = new Receiver(new Plupload($config));
        break;
    case 'fine-uploader':
        $receiver = new Receiver(new FineUploader($config));
        break;
    default:
        $receiver = new Receiver(new FileAPI($config));
        break;
}

return $receiver->receive($inputName)->send();
