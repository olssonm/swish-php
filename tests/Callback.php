<?php

use Olssonm\Swish\Callback;
use Olssonm\Swish\Payment;
use Olssonm\Swish\Payout;
use Olssonm\Swish\Refund;

test('callback', function () {
    $paymentCallback = '{
        "id": "5D59DA1B1632424E874DDB219AD54597",
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
    }';

    $refundCallback = '{
        "amount": "100.00",
        "originalPaymentReference": "5D59DA1B1632424E874DDB219AD54597",
        "dateCreated": "2020-10-29T13:40:27.950+0100",
        "payerPaymentReference": null,
        "payerAlias": "1231181189",
        "callbackUrl": "https://example.com/callback",
        "currency": "SEK",
        "id": "136A8AA7052F42CCB8563C78AB54C66B",
        "payeeAlias": null,
        "status": "DEBITED"
    }';

    $payoutCallback = '{
        "paymentReference": "3C33888EA83145EAA7078C8D25DFEC80",
        "payoutInstructionUUID": "5D59DA1B1632424E874DDB219AD54597",
        "payerPaymentReference": "Test",
        "callbackUrl": "https://example.com/callback",
        "callbackIdentifier": null,
        "payerAlias": "1234679304",
        "payeeAlias": "1234679304",
        "payeeSSN": "195810288083",
        "amount": 100,
        "currency": "SEK",
        "message": "Test",
        "payoutType": "PAYOUT",
        "status": "PAID",
        "dateCreated": "2024-08-09T09:57:11.224Z",
        "datePaid": "2024-08-09T09:57:15.224Z",
        "errorMessage": null,
        "additionalInformation": null,
        "errorCode": null
    }';

    $payment = Callback::parse($paymentCallback);
    $refund = Callback::parse($refundCallback);
    $payout = Callback::parse($payoutCallback);

    $this->assertInstanceOf(Payment::class, $payment);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $payment->id);

    $this->assertInstanceOf(Refund::class, $refund);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $refund->originalPaymentReference);
    $this->assertEquals('136A8AA7052F42CCB8563C78AB54C66B', $refund->id);

    $this->assertInstanceOf(Payout::class, $payout);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $payout->payoutInstructionUUID);
    $this->assertEquals('3C33888EA83145EAA7078C8D25DFEC80', $payout->paymentReference);
});
