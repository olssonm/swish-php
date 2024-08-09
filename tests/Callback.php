<?php

use Olssonm\Swish\Callback;
use Olssonm\Swish\Payment;
use Olssonm\Swish\Refund;

test('callback', function () {
    $paymentCallback = '{
        "id": "5D59DA1B1632424E874DDB219AD54597",
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
    }';

    $refundCallback = '{
        "amount": "100.00",
        "originalPaymentReference": "5D59DA1B1632424E874DDB219AD54597",
        "dateCreated": "2020-10-29T13:40:27.950+0100",
        "payerPaymentReference": null,
        "payerAlias": "1231181189",
        "callbackUrl": "https://9036c2a41fe0.ngrok.io/callback",
        "currency": "SEK",
        "id": "136A8AA7052F42CCB8563C78AB54C66B",
        "payeeAlias": null,
        "status": "DEBITED"
    }';

    $payment = Callback::parse($paymentCallback);
    $refund = Callback::parse($refundCallback);

    $this->assertInstanceOf(Payment::class, $payment);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $payment->id);

    $this->assertInstanceOf(Refund::class, $refund);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $refund->originalPaymentReference);
    $this->assertEquals('136A8AA7052F42CCB8563C78AB54C66B', $refund->id);
});
