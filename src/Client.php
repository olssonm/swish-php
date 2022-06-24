<?php

namespace Olssonm\Swish;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Olssonm\Swish\Api\Request;

/**
 * @mixin \Olssonm\Swish\Api\Request
 */
class Client
{
    use Request;

    protected GuzzleHttpClient $client;

    protected string $endpoint;

    public const PRODUCTION_ENDPOINT = 'https://cpc.getswish.net/swish-cpcapi/api/v2';

    public const TEST_ENDPOINT = 'https://mss.cpc.getswish.net/swish-cpcapi/api/v2';

    public function __construct(
        array $certificates,
        string $endpoint = self::PRODUCTION_ENDPOINT,
        ClientInterface $client = null
    ) {
        $this->setup($certificates, $endpoint, $client);
    }

    public function setup(
        array $certificates,
        string $endpoint = self::PRODUCTION_ENDPOINT,
        ClientInterface $client = null
    ): void {

        $handler = new HandlerStack();
        $handler->setHandler(new CurlHandler());

        $this->client = $client ?? new GuzzleHttpClient([
            'handler' => $handler,
            'curl' => [
                CURLOPT_TCP_KEEPALIVE => 1,
                CURLOPT_TCP_KEEPIDLE => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_CONNECTTIMEOUT => 20,
                'cert' => $certificates ?? null,
            ],
            'base_uri' => $endpoint,
            'http_errors' => false,
            'headers' => [
                'Accept-Encoding' => 'gzip'
            ],
        ]);
    }
}