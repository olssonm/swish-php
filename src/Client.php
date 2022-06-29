<?php

namespace Olssonm\Swish;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Olssonm\Swish\Api\Payments;
use Olssonm\Swish\Api\Refunds;
use InvalidArgumentException;

/**
 * @mixin \Olssonm\Swish\Api\Payments
 * @mixin \Olssonm\Swish\Api\Refunds
 */
class Client
{
    protected string $endpoint;

    protected array $history = [];

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
        $handler->push(Middleware::history($this->history));

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

    /**
     * Return the clients call-history
     *
     * @return array
     */
    public function getHistory()
    {
        return $this->history;
    }

    public function __call($method, $args)
    {
        if (!is_object($args[0])) {
            throw new InvalidArgumentException('Only Payment- and Refund-objects are allowed as first argument');
        }

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
