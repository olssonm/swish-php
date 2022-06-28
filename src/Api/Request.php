<?php

namespace Olssonm\Swish\Api;

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
use Olssonm\Swish\Util\Id;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \GuzzleHttp\Client $client
 */
trait Request
{
    /**
     * Fetch a payment.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function get(Payment $payment): Payment
    {
        $response = $this->call('GET', sprintf('v1/paymentrequests/%s', $payment->id));

        return new Payment(json_decode((string) $response->getBody(), true));
    }

    /**
     * Create a payment.
     *
     * @param Payment $payment
     * @return PaymentResult
     */
    public function create(Payment $payment): PaymentResult
    {
        $response = $this->call('PUT', sprintf('v2/paymentrequests/%s', $payment->id), [], json_encode($payment));

        return new PaymentResult([
            'id' => Id::parse($response),
            'location' => $response->getHeaderLine('Location') ?? null,
            'paymentRequestToken' => $response->getHeaderLine('PaymentRequestToken') ??  null,
        ]);
    }

    /**
     * Create a refund.
     *
     * @param Refund $refund
     * @return Refund
     */
    public function refund(Refund $refund): RefundResult
    {
        $response = $this->call('PUT', 'v2/refunds', [], json_encode($refund));

        return new RefundResult([
            'id' => Id::parse($response),
            'location' => $response->getHeaderLine('Location') ?? null
        ]);
    }

    /**
     * Cancel a payment.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function cancel(Payment $payment): Payment
    {
        $response = $this->call('PATCH', sprintf('v1/paymentrequests/%s', $payment->id), [
            'Content-Type' => 'application/json-patch+json'
        ], json_encode([[
            'op' => 'replace',
            'path' => '/status',
            'value' => 'cancelled',
        ]]));

        return new Payment(json_decode((string) $response->getBody(), true));
    }

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
    public function call(string $verb, string $uri, array $headers = [], $payload = null): Response
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
            case $status == 422:
                $this->triggerException(
                    ValidationException::class,
                    'Validation error',
                    $request,
                    $response
                );
            case $level == 4:
                $this->triggerException(
                    ClientException::class,
                    'Client error',
                    $request,
                    $response
                );
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
