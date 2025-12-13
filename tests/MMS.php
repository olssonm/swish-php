<?php

use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Payout;
use Olssonm\Swish\PayoutResult;
use Olssonm\Swish\QR;
use Olssonm\Swish\QRResult;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Olssonm\Swish\Util\Time;
use Olssonm\Swish\Util\Uuid;

// Make a full standard test against the MSS API with payments and refunds
test('full chain of requests', function () {

    $client = get_real_client();

    // New payment
    $payment = new Payment([
        'amount' => 100,
        'paymentRefeference' => 'abc123',
        'currency' => 'SEK',
        'message' => 'Kingston USB Flash Drive 8 GB',
        'callbackUrl' => 'https://example.com/callback',
        'payerAlias' => '4671234768',
        'payeeAlias' => '1231181189',
    ]);
    $id = $payment->id;

    $response = $client->create($payment);
    $this->assertEquals(201, $client->getHistory()[0]['response']->getStatusCode());
    $this->assertEquals($id, $response->id);
    $this->assertEquals(get_class($response), PaymentResult::class);

    // Get payment
    $response = $client->get(new Payment(['id' => $id]));
    $this->assertEquals(200, $client->getHistory()[1]['response']->getStatusCode());
    $this->assertEquals($id, $response->id);
    $this->assertEquals(get_class($response), Payment::class);

    // Refund a payment
    $refund = new Refund([
        'payerPaymentReference' => '0123456789',
        'originalPaymentReference' => 'abc123',
        'callbackUrl' => 'https://example.com/callback',
        'amount' => '100',
        'currency' => 'SEK',
        'payerAlias' => '1234567839',
        'message' => 'Refund for Kingston SSD Drive 320 GB',
    ]);
    $id = $refund->id;
    $response = $client->create($refund);
    $this->assertEquals(201, $client->getHistory()[2]['response']->getStatusCode());
    $this->assertEquals($id, $response->id);
    $this->assertEquals(get_class($response), RefundResult::class);

    // Get a refund
    $response = $client->get(new Refund(['id' => $refund->id]));
    $this->assertEquals(200, $client->getHistory()[3]['response']->getStatusCode());
    $this->assertEquals($refund->id, $response->id);
    $this->assertEquals(get_class($response), Refund::class);

    // Make payout
    $payout = new Payout([
        'payoutInstructionUUID' => Uuid::make(),
        'payerPaymentReference' => 'Test',
        'signingCertificateSerialNumber' => $client->getCertificate()->getSerial(),
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

    $response = $client->create($payout);
    $this->assertEquals(201, $client->getHistory()[4]['response']->getStatusCode());
    $this->assertEquals(get_class($response), PayoutResult::class);
    $this->assertEquals($payout->payoutInstructionUUID, $response->payoutInstructionUUID);

    // Make QR
    $payment = new Payment([
        'amount' => 100,
        'paymentRefeference' => 'abc123',
        'currency' => 'SEK',
        'message' => 'Kingston USB Flash Drive 8 GB',
        'callbackUrl' => 'https://example.com/callback',
        'payeeAlias' => '1231181189',
    ]);

    $id = $payment->id;

    $paymentResponse = $client->create($payment);
    $this->assertEquals(201, $client->getHistory()[0]['response']->getStatusCode());
    $this->assertEquals($id, $paymentResponse->id);
    $this->assertEquals(get_class($paymentResponse), PaymentResult::class);

    // Create QR
    $qr = new QR([
        'token' => $paymentResponse->paymentRequestToken,
        'format' => 'png',
        'size' => 600,
        'transparent' => true,
    ]);

    $qrResponse = $client->create($qr);
    $this->assertEquals(201, $client->getHistory()[0]['response']->getStatusCode());
    $this->assertEquals(get_class($qrResponse), QRResult::class);

    // Save the image for inspection
    file_put_contents(__DIR__ . '/output/qr-test.png', $qrResponse->data);

    // Save as .html with a base64 embedded image
    $html = '<html><body><img src="' . $qrResponse->toBase64() . '"/></body></html>';
    file_put_contents(__DIR__ . '/output/qr-test.html', $html);
});
