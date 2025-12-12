<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\QR;
use Olssonm\Swish\QRResult;

/**
 * @mixin \Olssonm\Swish\Client
 * @mixin \Olssonm\Swish\Api\AbstractResource
 */
class QRs extends AbstractResource
{
    /**
     * Get a QR code.
     *
     * @param QR $transaction
     * @throws \BadMethodCallException
     */
    public function get($transaction): never
    {
        throw new \BadMethodCallException('QRs can not be cancelled.');
    }

    /**
     * Create a QR code.
     *
     * @param QR $transaction
     * @return QRResult
     */
    public function create($transaction): QRResult
    {
        $response = $this->request(
            'POST',
            'https://mpc.getswish.net/qrg-swish/api/v1/commerce', // Note: special case for QR-codes
            [],
            (string) json_encode($transaction)
        );

        return new QRResult([
            'data' => $response->getBody()->getContents(),
            'contentType' => $response->getHeaderLine('Content-Type'),
            'format' => $transaction->format,
        ]);
    }

    /**
     * Cancel a QR code.
     *
     * @param QR $transaction
     * @throws \BadMethodCallException
     */
    public function cancel($transaction): never
    {
        throw new \BadMethodCallException('QRs can not be cancelled.');
    }
}
