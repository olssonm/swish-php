<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\Payout;
use Olssonm\Swish\PayoutResult;

class Payouts extends AbstractResource
{
    /**
     * Retrieve a payout.
     *
     * @param Payout $payment
     * @return Payout
     */
    public function get($payment): Payout
    {
        $response = $this->request('GET', sprintf('v1/paymentrequests/%s', $payment->id));

        return new Payout(json_decode((string) $response->getBody(), true));
    }

    /**
     * Create a payment.
     *
     * @param Payout $payment
     * @return PaymentResult
     */
    public function create($payout): PayoutResult
    {
        $response = $this->request('POST', 'v1/payouts', [], (string) json_encode(
            [
                'payload' => $payout,
                'callbackUrl' => '',
                'signature' => base64_encode(json_encode($payout))
            ]
        ));

        $location = $response->getHeaderLine('Location');
        $token = $response->getHeaderLine('PaymentRequestToken');

        return new PayoutResult([
            'id' => Id::parse($response),
            'location' => strlen($location) > 0 ? $location : null,
            'paymentRequestToken' => strlen($token) > 0 ? $token : null,
        ]);
    }

    /**
     * Cancel a payout.
     *
     * @param Payout $transaction
     * @throws \BadMethodCallException
     */
    public function cancel($transaction): void
    {
        throw new \BadMethodCallException('Payouts can not be cancelled.');
    }
}
