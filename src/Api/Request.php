<?php

namespace Olssonm\Swish\Api;

use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;

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
        $response = $this->call('PUT', '/payments', $payment->toArray());

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
        $response = $this->call('PUT', '/refund', $refund->toArray());

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
        $response = $this->call('PATCH', sprintf('/paymentrequests/%s', $payment->id), array_merge([
            'headers' => [
                'headers' => [
                    'Content-Type' => 'application/json-patch+json',
                    'Accept' => 'application/json'
                ],
            ],
            $payment->toArray()
        ]));

        return new Payment(json_decode((string) $response->getBody(), true));
    }

    /**
     * Main API caller
     *
     * @param string $verb
     * @param string $uri
     * @param array $payload
     * @return Response
     */
    public function call(string $verb, string $uri, array $payload = []): Response
    {
        $response = $this->client->request(
            $verb,
            $uri,
            empty($payload) ? [] : $payload
        );

        $responseBody = (string) $response->getBody();

        if ($response->getStatusCode() > 299) {
            // $this->handleRequestError($response);
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
        if (preg_match('/\/([^\/]+)$/', $response->getHeaderLine('Location'), $matches) === 1) {
            return $matches[1];
        }
        return null;
    }
}
