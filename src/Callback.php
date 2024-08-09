<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Exceptions\CallbackDecodingException;
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
            $content = (string) file_get_contents('php://input');
        }

        try {
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $th) {
            throw new CallbackDecodingException('Failed to decode JSON in Swish-callback', 0, $th);
        }

        // If the key 'originalPaymentReference' is set, assume refund
        if (isset($data['originalPaymentReference'])) {
            return new Refund($data);
        }
        // Assume payout if 'payoutInstructionUUID' is set
        elseif (isset($data['payoutInstructionUUID'])) {
            return new Payout($data);
        }

        return new Payment($data);
    }
}
