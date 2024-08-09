<?php

namespace Olssonm\Swish\Util;

use Carbon\Carbon;

class Time
{
    public static function make($timezone = 'Europe/Stockholm'): string
    {
        return Carbon::now($timezone)->toIso8601String();
    }
}
