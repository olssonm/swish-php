<?php

use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;
use Olssonm\Swish\Payout;
use Olssonm\Swish\Util\Time;
use Olssonm\Swish\Util\Uuid;

it('can retrieve certificate serial', function () {
    $certificate = new Certificate(
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'swish',
        __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
    );

    $this->assertEquals($certificate->getSerial(), '4512B3EBDA6E3CE6BFB14ABA6274A02C');
});

it('can perform payout', function() {

    $certificate = new Certificate(
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'swish',
        __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304.key',
    );

    $payout = new Payout([
        'payoutInstructionUUID' => Uuid::make(),
        'payerPaymentReference' => 'Test',
        'signingCertificateSerialNumber' => $certificate->getSerial(),
        'payerAlias' => '1234679304',
        'payeeAlias' => '1234679304',
        'payeeSSN' => '195810288083',
        'amount' => '100',
        'currency' => 'SEK',
        'payoutType' => 'PAYOUT',
        'message' => 'Test',
        'callbackUrl' => 'https://example.com/callback',
        'instructionDate' => Time::make(),
    ]);

    $client = get_real_client($certificate);

    $client->create($payout);
});
