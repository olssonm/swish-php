<?php

namespace Olssonm\Swish\Util;

use Psr\Http\Message\ResponseInterface;

class Id
{
    /**
     * Parse the ID from the response's Location-header.
     *
     * @param ResponseInterface $response
     * @return null|string
     */
    public static function parse(ResponseInterface $response): ?string
    {
        $url = parse_url($response->getHeaderLine('Location'), PHP_URL_PATH);
        return is_string($url) ? pathinfo($url, PATHINFO_BASENAME) : null;
    }
}
