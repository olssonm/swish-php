<?php

use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Payout;
use Olssonm\Swish\PayoutResult;
use Olssonm\Swish\QR;
use Olssonm\Swish\QRResult;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Olssonm\Swish\Util\Uuid;

it('can work with Payment-object', function () {
    $id = Uuid::make();
    $payment = new Payment([
        'id' => $id
    ]);

    $this->assertEquals($id, $payment->id);
    $this->assertEquals(['id' => $id], $payment->toArray());
    $this->assertEquals('{"id":"' . $id . '"}', json_encode($payment));
});

it('can fetch payment', function () {
    $id = UUID::make();
    $payment = new Payment([
        'id' => $id
    ]);

    $container = [];
    $client = get_mock_client(
        200,
        [],
        '{
            "id": "' . $id . '",
            "payeePaymentReference": "0123456789",
            "paymentReference": "1E2FC19E5E5E4E18916609B7F8911C12",
            "callbackUrl": "https://example.com/callback",
            "payerAlias": "4671234768",
            "payeeAlias": "1231181189",
            "amount": 100.00,
            "currency": "SEK",
            "message": "Kingston USB Flash Drive 8 GB",
            "status": "PAID",
            "dateCreated": "2019-01-02T14:29:51.092Z",
            "datePaid": "2019-01-02T14:29:55.093Z",
            "errorCode": null,
            "errorMessage": ""
        }',
        $container
    );

    $response = $client->get($payment);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/paymentrequests/' . $payment->id, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('GET', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('PAID', $response->status);
    $this->assertEquals($id, $response->id);
});

it('can make payment', function () {
    $id = UUID::make();
    $payment = new Payment();
    $payment->id = $id;
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payeeAlias = '1234679304';
    $payment->callbackUrl = 'https://example.com/callback';

    $container = [];
    $client = get_mock_client(201, [
        'Location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
        'PaymentRequestToken' => 'my-token'
    ], null, $container);

    $response = $client->create($payment);

    $this->assertEquals(201, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v2/paymentrequests/' . $id, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());

    $this->assertInstanceOf(PaymentResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id, $response->location);
    $this->assertEquals($id, $response->id);
    $this->assertEquals('my-token', $response->paymentRequestToken);
});

it('can make cancel payment request', function () {
    $payment = new Payment(['id' => '5D59DA1B1632424E874DDB219AD54597']);

    $container = [];
    $client = get_mock_client(
        200,
        [],
        '{
            "id":"5D59DA1B1632424E874DDB219AD54597",
            "payeePaymentReference":"0123456789",
            "paymentReference":"1E2FC19E5E5E4E18916609B7F8911C12",
            "callbackUrl": "https://example.com/callback",
            "payerAlias":"4671234768",
            "payeeAlias":"1231181189",
            "amount":100.00,
            "currency":"SEK",
            "message":"Kingston USB Flash Drive 8 GB",
            "status":"CANCELLED",
            "dateCreated":"2019-04-11T09:58:51.092Z",
            "datePaid":null
        }',
        $container
    );

    $response = $client->cancel($payment);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/paymentrequests/5D59DA1B1632424E874DDB219AD54597', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PATCH', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $response->id);
    $this->assertEquals('CANCELLED', $response->status);
});

it('can work with Refund-object', function () {
    $id = Uuid::make();
    $refund = new Refund([
        'id' => $id
    ]);

    $this->assertEquals($id, $refund->id);
    $this->assertEquals(['id' => $id], $refund->toArray());
    $this->assertEquals('{"id":"' . $id . '"}', json_encode($refund));
});

it('can make refund', function () {
    $id = Uuid::make();
    $refund = new Refund();
    $refund->id = $id;
    $refund->amount = 100;
    $refund->currency = 'SEK';
    $refund->payerAlias = '1231181189';
    $refund->message = 'Refund for Kingston USB Flash Drive 8 GB';

    $container = [];
    $client = get_mock_client(201, [
        'Location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
        'PaymentRequestToken' => 'my-token',
    ], null, $container);

    $response = $client->create($refund);

    $this->assertEquals(201, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v2/refunds/' . $id, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());

    $this->assertInstanceOf(RefundResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id, $response->location);
    $this->assertEquals($id, $response->id);
});

it('can work with Payout-object', function () {
    $id = Uuid::make();
    $payout = new Payout([
        'payoutInstructionUUID' => $id
    ]);

    $this->assertEquals($id, $payout->payoutInstructionUUID);
    $this->assertEquals([
        'payoutInstructionUUID' => $id,
        'currency' => 'SEK',
        'payoutType' => 'PAYOUT'
    ], $payout->toArray());
    $this->assertEquals('{"payoutInstructionUUID":"' . $id . '","currency":"SEK","payoutType":"PAYOUT"}', json_encode($payout));
});

it('can fetch payout', function () {
    $id = UUID::make();
    $payout = new Payout([
        'payoutInstructionUUID' => $id
    ]);

    $container = [];
    $client = get_mock_client(
        200,
        [],
        '{
            "paymentReference": "43DA7306F8DA426D8D7F82C939721031",
            "payoutInstructionUUID": "' . $id . '",
            "payerPaymentReference": "orderId",
            "callbackUrl": "https://example.com/callback",
            "payerAlias": "1234679304",
            "payeeAlias": "46768648198",
            "payeeSSN": "196210123235",
            "amount": 200,
            "currency": "SEK",
            "message": "message",
            "payoutType": "PAYOUT",
            "status": "PAID",
            "dateCreated": "2020-03-23T15: 17: 29.016Z",
            "datePaid": "2020-03-23T15: 17: 33.016Z",
            "errorMessage": null,
            "additionalInformation": null,
            "errorCode": null
        }',
        $container
    );

    $response = $client->get($payout);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/payouts/' . $payout->payoutInstructionUUID, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('GET', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payout::class, $response);

    $this->assertEquals('PAID', $response->status);
    $this->assertEquals($id, $response->payoutInstructionUUID);
});

it('can make payout', function () {
    $id = UUID::make();
    $payout = new Payout();
    $payout->payoutInstructionUUID = $id;
    $payout->amount = 100;
    $payout->currency = 'SEK';
    $payout->payeeAlias = '1234679304';
    $payout->callbackUrl = 'https://example.com/callback';

    $container = [];
    $client = get_mock_client(201, [
        'Location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/payouts/' . $id,
    ], null, $container);

    $response = $client->create($payout);

    $this->assertEquals(201, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/payouts', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('POST', $container[0]['request']->getMethod());

    $this->assertInstanceOf(PayoutResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/payouts/' . $id, $response->location);
    $this->assertEquals($id, $response->payoutInstructionUUID);
});

it('can make qr', function () {
    $id = UUID::make();
    $payment = new Payment();
    $payment->id = $id;
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payeeAlias = '1234679304';
    $payment->callbackUrl = 'https://example.com/callback';

    $container = [];
    $client = get_mock_client(201, [
        'Location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
        'PaymentRequestToken' => '1234567890abcdef'
    ], null, $container);

    $response = $client->create($payment);

    $qr = new QR([
        'token' => $response->paymentRequestToken,
        'format' => 'png',
    ]);

    $container = [];
    $client = get_mock_client(201, [
        'Location' => 'https://mpc.getswish.net/qrg-swish/api/v1/commerce',
        'Content-Type' => 'image/png'
    ], file_get_contents(__DIR__ . '/dummy/qr.png'), $container);

    $response = $client->create($qr);

    $this->assertInstanceOf(QRResult::class, $response);
    $this->assertEquals(file_get_contents(__DIR__ . '/dummy/qr.png'), (string) $response);
    $this->assertEquals(file_get_contents(__DIR__ . '/dummy/qr.png'), (string) $response->data);
    $this->assertEquals('image/png', $response->contentType);
    $this->assertEquals('png', $response->format);
});
