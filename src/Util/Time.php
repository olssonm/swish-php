<?php

namespace Olssonm\Swish\Util;

use Carbon\Carbon;

class Time
{
    /**
     * Retrieve the current time in an appropriate format.
     *
     * @param string $timezone
     * @return string
     */
    public static function make(string $timezone = 'Europe/Stockholm'): string
    {
        return Carbon::now($timezone)->toIso8601String();
    }
}
