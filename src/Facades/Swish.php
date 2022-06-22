<?php

namespace Olssonm\Swish\Facades;

use Illuminate\Support\Facades\Facade;

class Swish extends Facade
{
    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    protected static function getFacadeAccessor()
    {
        return 'swish';
    }
}
