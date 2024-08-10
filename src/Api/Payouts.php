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
    public function get($payout): Payout
    {
        $response = $this->request('GET', sprintf('v1/payouts/%s', $payout->payoutInstructionUUID));

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
        $certificate = $this->swish->getCertificate()->getSigningCertificate();
        $signature = Crypto::hashAndSign($payout, $certificate);

        $response = $this->request('POST', 'v1/payouts', [], (string) json_encode(
            [
                'payload' => $payout,
                'callbackUrl' => $payout->callbackUrl,
                'signature' => $signature,
            ]
        ));

        $location = $response->getHeaderLine('Location');

        return new PayoutResult([
            'payoutInstructionUUID' => Id::parse($response),
            'location' => strlen($location) > 0 ? $location : null
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
