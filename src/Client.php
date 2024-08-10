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
use Olssonm\Swish\Api\Payouts;

/**
 * @mixin \Olssonm\Swish\Api\Payments
 * @mixin \Olssonm\Swish\Api\Refunds
 */
class Client
{
    protected string $endpoint;

    protected Certificate $certificate;

    /**
     * @var array<mixed>
     */
    protected array $history = [];

    public const PRODUCTION_ENDPOINT = 'https://cpc.getswish.net/swish-cpcapi/api/';

    public const TEST_ENDPOINT = 'https://mss.cpc.getswish.net/swish-cpcapi/api/';

    protected ClientInterface $client;

    public function __construct(
        Certificate $certificate = null,
        string $endpoint = self::PRODUCTION_ENDPOINT,
        ClientInterface $client = null
    ) {
        $this->setup($certificate, $endpoint, $client);
    }

    public function setup(
        Certificate $certificate = null,
        string $endpoint = self::PRODUCTION_ENDPOINT,
        ClientInterface $client = null
    ): void {

        if ($certificate) {
            $this->setCertificate($certificate);
        }

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
            'verify' => $certificate?->getRootCertificate(),
            'cert' => $certificate?->getClientCertificate(),
            'base_uri' => $endpoint,
            'http_errors' => false,
        ]);
    }

    /**
     * Return the clients call-history
     *
     * @return array<mixed>
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Return the clients call-history
     *
     * @return Certificate
     */
    public function getCertificate(): Certificate
    {
        return $this->certificate;
    }

    /**
     * Set the certificate
     *
     * @param Certificate $certificate
     * @return void
     */
    public function setCertificate(Certificate $certificate): void
    {
        $this->certificate = $certificate;
    }

    /**
     * @param array<mixed> $args
     */
    public function __call(string $method, array $args): mixed
    {
        if (
            !is_object($args[0]) ||
            (
                (get_class($args[0]) != Payment::class) &&
                (get_class($args[0]) != Refund::class) &&
                (get_class($args[0]) != Payout::class)
            )
        ) {
            throw new InvalidArgumentException(
                'Only Payment-, Payout- and Refund-objects are allowed as first argument'
            );
        }

        switch (get_class($args[0])) {
            case Payment::class:
                $class = new Payments($this->client);
                break;

            case Refund::class:
                $class = new Refunds($this->client);
                break;

            case Payout::class:
                $class = new Payouts($this->client, $this);
                break;
        }

        // @phpstan-ignore-next-line
        return call_user_func_array([$class, $method], $args);
    }
}
