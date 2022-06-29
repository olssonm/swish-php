<?php

namespace Olssonm\Swish\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Exceptions\ClientException;
use Olssonm\Swish\Exceptions\ServerException;
use Olssonm\Swish\Exceptions\ValidationException;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractResource
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    abstract public function get($transaction);

    abstract public function create($transaction);

    abstract public function cancel($transaction);

    /**
     * Main API caller
     *
     * @param string $verb
     * @param string $uri
     * @param array $headers
     * @param string|null $payload
     * @return Response
     * @throws ClientException|ServerException|ValidationException
     */
    public function request(string $verb, string $uri, array $headers = [], $payload = null): Response
    {
        $request = new Psr7Request(
            $verb,
            $uri,
            array_merge([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ], $headers),
            $payload
        );

        $response = $this->client->send($request);

        $status = $response->getStatusCode();
        $level = (int) \floor($status / 100);

        switch (true) {
            case $status == 403:
                // No break
            case $status == 422:
                $this->triggerException(
                    ValidationException::class,
                    'Validation error',
                    $request,
                    $response
                );
                // No break
            case $level == 4:
                $this->triggerException(
                    ClientException::class,
                    'Client error',
                    $request,
                    $response
                );
                // No break
            case $level == 5:
                $this->triggerException(
                    ServerException::class,
                    'Server error',
                    $request,
                    $response
                );
        }

        return $response;
    }

    /**
     * Trigger a request exception
     *
     * @param string $class
     * @param string $label
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return RequestException
     */
    private function triggerException(
        string $class,
        string $label,
        RequestInterface $request,
        ResponseInterface $response
    ): void {
        $message = \sprintf(
            '%s: `%s %s` resulted in a `%s %s` response',
            $label,
            $request->getMethod(),
            $request->getUri()->__toString(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        );

        throw new $class(
            $message,
            $request,
            $response
        );
    }
}
