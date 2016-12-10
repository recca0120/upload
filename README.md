## Pure Ajax Upload And for Laravel 5 (Support jQuery-File-Upload, FileApi, Plupload)

[![StyleCI](https://styleci.io/repos/48772854/shield?style=flat)](https://styleci.io/repos/48772854)
[![Build Status](https://travis-ci.org/recca0120/laravel-upload.svg)](https://travis-ci.org/recca0120/laravel-upload)
[![Total Downloads](https://poser.pugx.org/recca0120/upload/d/total.svg)](https://packagist.org/packages/recca0120/upload)
[![Latest Stable Version](https://poser.pugx.org/recca0120/upload/v/stable.svg)](https://packagist.org/packages/recca0120/upload)
[![Latest Unstable Version](https://poser.pugx.org/recca0120/upload/v/unstable.svg)](https://packagist.org/packages/recca0120/upload)
[![License](https://poser.pugx.org/recca0120/upload/license.svg)](https://packagist.org/packages/recca0120/upload)
[![Monthly Downloads](https://poser.pugx.org/recca0120/upload/d/monthly)](https://packagist.org/packages/recca0120/upload)
[![Daily Downloads](https://poser.pugx.org/recca0120/upload/d/daily)](https://packagist.org/packages/recca0120/upload)

## Features
- Support Chunks [jQuery-File-Upload](https://github.com/blueimp/jQuery-File-Upload) $driver = 'fileapi';
- Support Chunks [FileApi](http://mailru.github.io/FileAPI/) $driver = 'fileapi';
- Support Chunks [Plupload](http://www.plupload.com/) $driver = 'plupload';

## Installing

To get the latest version of Laravel Exceptions, simply require the project using [Composer](https://getcomposer.org):

```bash
composer require recca0120/upload
```

Instead, you may of course manually update your require block and run `composer update` if you so choose:

```json
{
    "require": {
        "recca0120/upload": "^1.2.0"
    }
}
```

## Standalone

```php

use Recca0120\Upload\Receiver;
use Recca0120\Upload\Uploaders\FileAPI;
use Recca0120\Upload\Uploaders\Plupload;

require __DIR__.'/vendor/autoload.php';

$config = [
    'chunk_path' => 'absolute path';
    'base_path' => 'absolute path',
    'base_url' => 'http://dev/'
];

$inputName = 'file';
$destinationPath = 'relative path';

$receiver = new Receiver(new FileAPI($config));
// save to $config['base_path'].'/'.$destinationPath;
echo $receiver->save($inputName, $destinationPath);
```

## Laravel 5

Include the service provider within `config/app.php`. The service povider is needed for the generator artisan command.

```php
'providers' => [
    ...
    Recca0120\Upload\UploadServiceProvider::class,
    ...
];
```

publish

```php
artisan vendor:publish --provider="Recca0120\Upload\UploadServiceProvider"
```

## How to use

Controller
```php

use Recca0120\Upload\UploadManager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadController extends Controller
{
    public function upload(UploadManager $manager)
    {
        $driver = 'plupload'; // or 'fileapi'
        $inputName = 'file'; // $_FILES index;
        $destinationPath = 'storage/temp';
        return $manager
            ->driver($driver)
            ->save($inputName, destinationPath);
    }
}
```
