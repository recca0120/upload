<?php

use Recca0120\Upload\Dropzone;
use Recca0120\Upload\FileAPI;
use Recca0120\Upload\FineUploader;
use Recca0120\Upload\Plupload;
use Recca0120\Upload\Receiver;

include __DIR__.'/vendor/autoload.php';

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

    case 'file-pond':
        if (! empty($_POST['file'])) {
            echo '12345.png';
//            var_dump($_SERVER['HTTP_UPLOAD_LENGTH']);
            exit;
        }
        if ($_GET['patch']) {
            var_dump($_SERVER['HTTP_UPLOAD_NAME']);
            var_dump($_SERVER['HTTP_UPLOAD_LENGTH']);
            var_dump($_SERVER['HTTP_UPLOAD_OFFSET']);
            var_dump($_SERVER['HTTP_CONTENT_TYPE']);
            $mode = $_SERVER['HTTP_UPLOAD_OFFSET'] === '0' ? 'wb+' : 'ab+';
            $fp = fopen(__DIR__.'/test.png', $mode);
            fseek($fp, $_SERVER['HTTP_UPLOAD_OFFSET']);
            fwrite($fp, file_get_contents('php://input'));
            fclose($fp);
        }
        exit;
        break;

    default:
        $receiver = new Receiver(new FileAPI($config));
        break;
}

$receiver->receive($inputName)->send();
