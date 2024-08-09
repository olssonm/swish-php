<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\Payout;
use Olssonm\Swish\PayoutResult;
use Olssonm\Swish\Util\Crypto;
use Olssonm\Swish\Util\Hash;
use Olssonm\Swish\Util\Id;

/**
 * @mixin \Olssonm\Swish\Client
 * @mixin \Olssonm\Swish\Api\AbstractResource
 */
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
     * @param Payout $payout
     * @return PayoutResult
     */
    public function create($payout): PayoutResult
    {
        $signature = Crypto::hashAndSign($payout, $this->swish->getCertificate()->getSigningCertificate());

        $response = $this->request('POST', 'v1/payouts', [], (string) json_encode(
            [
                'payload' => $payout,
                'callbackUrl' => $payout->callbackUrl,
                'signature' => $signature,
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
