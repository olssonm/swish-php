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
        $response = $this->request('PUT', sprintf('v2/paymentrequests/%s', $payment->id), [], json_encode($payment));

        return new PaymentResult([
            'id' => Id::parse($response),
            'location' => $response->getHeaderLine('Location') ?? null,
            'paymentRequestToken' => $response->getHeaderLine('PaymentRequestToken') ?? null,
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
        ], json_encode([[
            'op' => 'replace',
            'path' => '/status',
            'value' => 'cancelled',
        ]]));

        return new Payment(json_decode((string) $response->getBody(), true));
    }
}
