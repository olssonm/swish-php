<?php

namespace Olssonm\Swish\Api;

use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Exceptions\ClientException;
use Olssonm\Swish\Exceptions\ServerException;
use Olssonm\Swish\Exceptions\ValidationException;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;

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
        $response = $this->call('GET', sprintf('/payments/%s', $payment->id));

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
        $response = $this->call('PUT', '/payments', [], json_encode($payment));

        return new PaymentResult([
            'id' => $this->parseId($response),
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
        $response = $this->call('PUT', '/refund', [], json_encode($refund));

        return new RefundResult([
            'id' => $this->parseId($response),
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
        $response = $this->call('PATCH', sprintf('/paymentrequests/%s', $payment->id), [
            'headers' => [
                'Content-Type' => 'application/json-patch+json',
                'Accept' => 'application/json'
            ],
        ]);

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
        $request = new Psr7Request($verb, $uri, $headers, $payload);
        $response = $this->client->send($request);

        $status = $response->getStatusCode();
        $level = (int) \floor($status / 100);

        switch (true) {
            case $status == 403:
            case $status == 422:
                throw new ValidationException(
                    $response->getBody()->getContents(),
                    $request,
                    $response
                );
                break;
            case $level == 4:
                throw new ClientException(
                    $response->getBody()->getContents(),
                    $request,
                    $response
                );
            case $level == 5:
                throw new ServerException(
                    $response->getBody()->getContents(),
                    $request,
                    $response
                );
                break;
        }

        return $response;
    }

    /**
     * Parse the ID from the response's Location-header.
     *
     * @param Response $response
     * @return mixed
     */
    private function parseId(Response $response)
    {
        return pathinfo(parse_url($response->getHeaderLine('Location'), PHP_URL_PATH), PATHINFO_BASENAME);
    }
}
