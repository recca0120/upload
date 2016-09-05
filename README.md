## Ajax Upload for Laravel 5 (Support jQuery-File-Upload, FileApi, Plupload)

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

Include the service provider within `config/app.php`. The service povider is needed for the generator artisan command.

```php
'providers' => [
    ...
    Recca0120\Upload\ServiceProvider::class,
    ...
];
```

## How to use

Controller
```php

use Recca0120\Upload\Manager;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadController extends Controller
{
    public function upload(Manager $manager)
    {
        $driver = 'plupload'; // or 'fileapi'
        $name = 'file'; // $_FILES index;
        return $manager
            ->driver($driver)
            ->receive($name, function (UploadedFile $file) {

                $clientOriginalName = $file->getClientOriginalName();
                $pathName = $file->getPathname();
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                return response()->json([
                    'name'     => $clientOriginalName,
                    'type'     => $mimeType,
                    'size'     => $size,
                ]);
            });
    }
}
```
