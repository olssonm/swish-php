<?php

namespace Olssonm\Swish\Exceptions;

class InvalidUuidException extends \InvalidArgumentException
{
    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this('Invalid UUID ' . get_class($this), $code);
        }
    }
}
