<?php

namespace Recca0120\Upload\Facades;

use Illuminate\Support\Facades\Facade;

class AjaxUpload extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ajaxupload';
    }
}
