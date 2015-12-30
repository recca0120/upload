# Laravel AjaxUpload
support plupload, fileapi

## Installation

Add Presenter to your composer.json file:

```js
"require": {
    "recca0120/upload": "~0.0.1"
}
```
Now, run a composer update on the command line from the root of your project:

    composer update

### Registering the Package

Include the service provider within `app/config/app.php`. The service povider is needed for the generator artisan command.

```php
'providers' => [
    ...
    Recca0120\Upload\ServiceProvider::class,
    ...
];
```

Now publish the config file and migrations by running `php artisan vendor:publish`. The config file will give you control over which storage engine to use as well as some storage-specific settings.

_IMPORTANT_ if you are using the database driver don't forget to migrate the database by running `php artisan migrate`

### Add Facades

Add this to your facades in config/app.php:

```php
'aliases' => [
    ...
    'AjaxUpload' => \Recca0120\Upload\Facades\AjaxUpload::class,
    ...
];
```

## Usage

```php
class MediaController extends Controller
{
    public function postUpload()
    {
        // if you use plupload https://github.com/moxiecode/plupload
        $driver = 'plupload';
        // if you use fileapi https://github.com/mailru/FileAPI.git
        // $driver = 'plupload';
        return AjaxUpload::driver($driver)->receive('file', function (UploadedFile $file) {
            $pathName = $file->getPathname();
            $clientOriginalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType();
            $size = $file->getSize();
        });
    }
}
```

## License

The contents of this repository is released under the [MIT license](http://opensource.org/licenses/MIT).
