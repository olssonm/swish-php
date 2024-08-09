<?php

use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Payout;
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
            "callbackUrl": "https://example.com/api/swishcb/paymentrequests",
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
    $payment->payee = '123456789';
    $payment->payeeAlias = '1234679304';
    $payment->callbackUrl = 'https://example.com';

    $container = [];
    $client = get_mock_client(201, [
        'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
        'paymentrequesttoken' => 'my-token'
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
            "callbackUrl": "https://example.com/api/swishcb/paymentrequests",
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
        'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
        'paymentrequesttoken' => 'my-token',
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

it('can make payout', function () {

});
