<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Util\Uuid;

/**
 * @property string $payoutInstructionUUID
 * @property string $payerPaymentReference
 * @property string $payerAlias
 * @property string $payeeAlias
 * @property string $payeeSSN
 * @property string $amount
 * @property string $currency
 * @property string $payoutType
 * @property string $message
 * @property string $signingCertificateSerialNumber
 * @property string $status
 * @property string $instructionDate
 * @property string $dateCreated
 * @property string $datePaid
 * @property string $additionalInformation
 */
class Payout extends Resource
{
    public string $callbackUrl = '';

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        // Assume some default details
        $this->payoutInstructionUUID = $this->payoutInstructionUUID ?? Uuid::make();
        $this->currency = $this->currency ?? 'SEK';
        $this->payoutType = $this->payoutType ?? 'PAYOUT';
    }

    public function __get(string $key): mixed
    {
        // @codeCoverageIgnoreStart
        if (property_exists($this, $key)) {
            return $this->{$key};
        }
        // @codeCoverageIgnoreEnd
        return parent::__get($key);
    }

    public function __set(string $key, mixed $value)
    {
        // @codeCoverageIgnoreStart
        if (property_exists($this, $key)) {
            $this->{$key} = $value;
            return;
        }
        // @codeCoverageIgnoreEnd
        parent::__set($key, $value);
    }
}
