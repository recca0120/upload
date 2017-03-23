<?php

use Recca0120\Upload\Receiver;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\Plupload;

include __DIR__.'/vendor/autoload.php';

$config = [
    'chunks' => 'temp/chunks',
    'storage' => 'temp',
    'domain' => 'http://app.dev/',
    'path' => 'temp',
];

$inputName = 'file';
$api = $_GET['api'];

if ($api === 'fileapi') {
    $receiver = new Receiver(new FileAPI($config));
} else {
    $receiver = new Receiver(new Plupload($config));
}

return $receiver->receive($inputName)->send();
