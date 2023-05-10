<?php

namespace Olssonm\Swish\Exceptions;

class InvalidCertificatePathException extends \InvalidArgumentException
{
    public function __construct($message = null, $code = 0)
    {
        if (!$message) {
            throw new $this('Invalid path for root- or client certificate (file doesn\'t exist) ' . get_class($this), $code);
        }
    }
}
