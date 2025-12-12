<?php

namespace Olssonm\Swish\Api;

use Olssonm\Swish\QRResult;

/**
 * @mixin \Olssonm\Swish\Client
 * @mixin \Olssonm\Swish\Api\AbstractResource
 */
class QRs extends AbstractResource
{
    public function create($qr)
    {
        $response = $this->request(
            'POST',
            'https://mpc.getswish.net/qrg-swish/api/v1/commerce', // Note: special case for QR-codes
            [],
            (string) json_encode($qr)
        );

        return new QRResult([
            'data' => $response->getBody()->getContents(),
            'contentType' => $response->getHeaderLine('Content-Type'),
            'format' => $qr->format,
        ]);
    }

    public function get($qr)
    {
        throw new \BadMethodCallException('QRs can not be cancelled.');
    }

    public function cancel($qr)
    {
        throw new \BadMethodCallException('QRs can not be cancelled.');
    }
}
