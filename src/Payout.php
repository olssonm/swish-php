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
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->payoutInstructionUUID = $this->payoutInstructionUUID ?? Uuid::make();

        // Assume some default details
        $this->currency = $this->currency ?? 'SEK';
        $this->payoutType = $this->payoutType ?? 'PAYOUT';
    }
}
