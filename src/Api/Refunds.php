<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Olssonm\Swish\Util\Id;

/**
 * @mixin \Olssonm\Swish\Api\AbstractResource
 */
class Refunds extends AbstractResource
{
    /**
     * Retrieve a refund.
     *
     * @param Refund $refund
     * @return Refund
     */
    public function get($refund): Refund
    {
        $response = $this->request('GET', sprintf('v1/refunds/%s', $refund->id));

        return new Refund(json_decode((string) $response->getBody(), true));
    }

    /**
     * Create a refund.
     *
     * @param Refund $refund
     * @return RefundResult
     */
    public function create($refund): RefundResult
    {
        $response = $this->request('PUT', sprintf('v2/refunds/%s', $refund->id), [], json_encode($refund));

        return new RefundResult([
            'id' => Id::parse($response),
            'location' => $response->getHeaderLine('Location') ?? null,
        ]);
    }

    /**
     * Cancel a refund.
     *
     * @param Refund $transaction
     * @throws BadMethodCallException
     */
    public function cancel($transaction): void
    {
        throw new \BadMethodCallException('Refunds can not be cancelled.');
    }
}
