<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Util\Id;

/**
 * @mixin \Olssonm\Swish\Api\AbstractResource
 */
class Payments extends AbstractResource
{
    /**
     * Retrieve a payment.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function get($payment): Payment
    {
        $response = $this->request('GET', sprintf('v1/paymentrequests/%s', $payment->id));

        return new Payment(json_decode((string) $response->getBody(), true));
    }

    /**
     * Create a payment.
     *
     * @param Payment $payment
     * @return PaymentResult
     */
    public function create($payment): PaymentResult
    {
        $response = $this->request(
            'PUT',
            sprintf('v2/paymentrequests/%s', $payment->id),
            [],
            (string) json_encode($payment)
        );

        $location = $response->getHeaderLine('Location');
        $token = $response->getHeaderLine('PaymentRequestToken');

        return new PaymentResult([
            'id' => Id::parse($response),
            'location' => strlen($location) > 0 ? $location : null,
            'paymentRequestToken' => strlen($token) > 0 ? $token : null,
        ]);
    }

    /**
     * Cancel a payment.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function cancel($payment): Payment
    {
        $response = $this->request('PATCH', sprintf('v1/paymentrequests/%s', $payment->id), [
            'Content-Type' => 'application/json-patch+json',
        ], (string) json_encode([[
            'op' => 'replace',
            'path' => '/status',
            'value' => 'cancelled',
        ]]));

        return new Payment(json_decode((string) $response->getBody(), true));
    }
}
