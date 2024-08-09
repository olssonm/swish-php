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

    $this->assertEquals($certificate->getSerial(), '4EF5C55AA5E475A3611087A4897F3F13');
});

it('can perform payout', function() {

    $certificate = new Certificate(
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'swish',
        __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
    );

    $payout = new Payout([
        'payoutInstructionUUID' => Uuid::make(),
        'payerPaymentReference' => 'Test',
        'signingCertificateSerialNumber' => $certificate->getSerial(),
        'payerAlias' => '4671234768',
        'payeeAlias' => '1231181189',
        'payeeSSN' => '199801012345',
        'amount' => '100',
        'currency' => 'SEK',
        'payoutType' => 'PAYOUT',
        'message' => 'Test',
        'instructionDate' => Time::make(),
    ]);
    $client = get_real_client($certificate);

    $client->create($payout);
});

function get_real_client($certificate)
{
    return new Client($certificate, Client::TEST_ENDPOINT);
}
