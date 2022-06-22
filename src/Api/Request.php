<?php

namespace Olssonm\Swish\Api;

use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Refund;

trait Request
{
    public function get(Payment $payment): Payment
    {
        $response = $this->call('PUT', sprintf('/payments/%s', $payment->id));
        return new Payment(json_decode((string) $response->getBody(), true));
    }

    public function create(Payment $payment): PaymentResult
    {
        $response = $this->call('PUT', '/payments', $payment->toArray());

        $parseId = function(Response $response) {
            if (preg_match('/\/([^\/]+)$/', $response->getHeaderLine('Location'), $matches) === 1) {
                return $matches[1];
            }
            return null;
        };

        return new PaymentResult([
            'id' => $parseId($response),
            'location' => $response->getHeader('Location') ?? null,
            'paymentRequestToken' => $response->getHeader('PaymentRequestToken') ??  null,
        ]);
    }

    public function refund(Refund $refund): Refund
    {
        $response = $this->call('PUT', '/refund', $refund->toArray());
        return new Refund(json_decode((string) $response->getBody(), true));
    }

    public function cancel(Payment $payment)
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

    protected function call(string $verb, string $uri, array $payload = []): Response
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

}
