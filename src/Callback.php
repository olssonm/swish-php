<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Refund;
use Olssonm\Swish\Payment;

class Callback
{
    /**
     * @param string $content
     *
     * @return Payment|Refund
     */
    public static function parse($content = null)
    {
        if (is_null($content)) {
            $content = file_get_contents('php://input');
        }

        $data = json_decode($content);

        // If the key 'originalPaymentReference' is not set, assume refund
        if (isset($data->originalPaymentReference)) {
            return new Refund($data);
        }

        return new Payment($data);
    }
}
