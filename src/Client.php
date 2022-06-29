<?php

namespace Olssonm\Swish;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Olssonm\Swish\Api\Payments;
use Olssonm\Swish\Api\Refunds;

/**
 * @mixin \Olssonm\Swish\Api\Payments
 * @mixin \Olssonm\Swish\Api\Refunds
 */
class Client
{
    protected string $endpoint;

    public const PRODUCTION_ENDPOINT = 'https://cpc.getswish.net/swish-cpcapi/api/';

    public const TEST_ENDPOINT = 'https://mss.cpc.getswish.net/swish-cpcapi/api/';

    protected GuzzleHttpClient $client;

    public function __construct(
        array $certificate,
        string $endpoint = self::PRODUCTION_ENDPOINT,
        ClientInterface $client = null
    ) {
        $this->setup($certificate, $endpoint, $client);
    }

    public function setup(
        array $certificate,
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
            ],
            'verify' => true,
            'cert' => $certificate,
            'base_uri' => $endpoint,
            'http_errors' => false,
        ]);
    }

    public function __call($method, $args)
    {
        switch (get_class($args[0])) {
            case Payment::class:
                $class = new Payments($this->client);
                break;

            default:
                $class = new Refunds($this->client);
                break;
        }

        return call_user_func_array([$class, $method], $args);
    }
}
