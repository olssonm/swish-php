<?php

use Olssonm\Swish\Callback;
use Olssonm\Swish\Exceptions\CallbackDecodingException;
use Olssonm\Swish\Exceptions\ClientException;
use Olssonm\Swish\Exceptions\InvalidUuidException;
use Olssonm\Swish\Exceptions\ServerException;
use Olssonm\Swish\Exceptions\ValidationException;
use Olssonm\Swish\Payment;
use Olssonm\Swish\Refund;

it('throws InvalidUuidException', function () {
    $this->expectException(InvalidUuidException::class);
    new Payment(['id' => 'invalid-uuid']);
});

it('throws BadMethodCallException', function () {
    $this->expectException(\BadMethodCallException::class);

    $container = [];
    $client = get_mock_client(200, [], null, $container);
    $client->cancel(new Refund(['id' => '5D59DA1B1632424E874DDB219AD54597']));
});

it('throws ValidationException', function () {
    $payment = new Payment();
    $payment->id = '5D59DA1B1632424E874DDB219AD54597';
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payee = '123456789';

    $container = [];
    $client = get_mock_client(422, [], '{
            "id": "5D59DA1B1632424E874DDB219AD54597",
            "payeePaymentReference": "0123456789",
            "paymentReference": "1E2FC19E5E5E4E18916609B7F8911C12",
            "callbackUrl": "",
            "payerAlias": "4671234768",
            "payeeAlias": "1231181189",
            "amount": 100.00,
            "currency": "SEK",
            "message": "Kingston USB Flash Drive 8 GB",
            "status": "PAID",
            "dateCreated": "2019-01-02T14:29:51.092Z",
            "datePaid": "2019-01-02T14:29:55.093Z",
            "errorCode": "RP03",
            "errorMessage": "Callback URL is missing or does not use HTTPS."
        }', $container);

    try {
        $response = $client->create($payment);
    } catch (ValidationException $e) {
        $this->assertInstanceOf(ValidationException::class, $e);
        $this->assertEquals($e->getErrors()[0]->errorCode, 'RP03');
        $this->assertEquals($e->getErrors()[0]->errorMessage, 'Callback URL is missing or does not use HTTPS.');
    }

    $this->assertEquals(422, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v2/paymentrequests/5D59DA1B1632424E874DDB219AD54597', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());
});

it('throws InvalidArgumentException', function () {
    $this->expectException(InvalidArgumentException::class);

    $container = [];
    $client = get_mock_client(429, [], null, $container);

    $client->create(new stdClass());
});

it('throws ClientException', function () {
    $this->expectException(ClientException::class);

    $container = [];
    $client = get_mock_client(429, [], null, $container);

    $client->create(new Payment());
});

it('throws ServerException', function () {
    $this->expectException(ServerException::class);

    $container = [];
    $client = get_mock_client(500, [], null, $container);
    $client->create(new Payment());
});

it('throws CallbackDecodingException on invalid', function () {
    $this->expectException(CallbackDecodingException::class);
    Callback::parse('invalid');
});

it('throws CallbackDecodingException on null', function () {
    $this->expectException(CallbackDecodingException::class);
    Callback::parse(null);
});
