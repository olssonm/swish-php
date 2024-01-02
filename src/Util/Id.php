<?php

namespace Olssonm\Swish\Util;

use GuzzleHttp\Psr7\Response;

class Id
{
    /**
     * Parse the ID from the response's Location-header.
     *
     * @param Response $response
     * @return null|string
     */
    public static function parse(Response $response): ?string
    {
        $url = parse_url($response->getHeaderLine('Location'), PHP_URL_PATH);
        return is_string($url) ? pathinfo($url, PATHINFO_BASENAME) : null;
    }
}
