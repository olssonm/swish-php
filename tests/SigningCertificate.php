<?php

namespace Olssonm\Swish\Test;

enum SigningCertificate: int
{
    case TRUE = 1;
    case FALSE = 2;
    case BAD = 3;
    case EMPTY = 4;

    public function isTrue(): bool
    {
        return $this === self::TRUE;
    }

    public function isFalse(): bool
    {
        return $this === self::FALSE;
    }

    public function isBad(): bool
    {
        return $this === self::BAD;
    }

    public function isEmpty(): bool
    {
        return $this === self::EMPTY;
    }
}
