<?php

namespace Smartman\Swedbank\Facade;

use Illuminate\Support\Facades\Facade;

class Swedbank extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'swedbank';
    }
}