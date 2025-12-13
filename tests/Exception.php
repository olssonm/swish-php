<?php

use Olssonm\Swish\Callback;
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Error;
use Olssonm\Swish\Exceptions\CallbackDecodingException;
use Olssonm\Swish\Exceptions\CertificateDecodingException;
use Olssonm\Swish\Exceptions\ClientException;
use Olssonm\Swish\Exceptions\InvalidUuidException;
use Olssonm\Swish\Exceptions\ServerException;
use Olssonm\Swish\Exceptions\ValidationException;
use Olssonm\Swish\Payment;
use Olssonm\Swish\Payout;
use Olssonm\Swish\QR;
use Olssonm\Swish\Refund;
use Olssonm\Swish\Test\SigningCertificate;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

it('throws InvalidUuidException', function () {
    $this->expectException(InvalidUuidException::class);
    new Payment(['id' => 'invalid-uuid']);
});

it('throws BadMethodCallException on Refund', function () {
    $this->expectException(\BadMethodCallException::class);

    $container = [];
    $client = get_mock_client(200, [], null, $container);
    $client->cancel(new Refund(['id' => '5D59DA1B1632424E874DDB219AD54597']));
});

it('throws BadMethodCallException on Payout', function () {
    $this->expectException(\BadMethodCallException::class);

    $container = [];
    $client = get_mock_client(200, [], null, $container);
    $client->cancel(new Payout(['payoutInstructionUUID' => '5D59DA1B1632424E874DDB219AD54597']));
});

it('throws BadMethodCallException on QR get', function () {
    $this->expectException(\BadMethodCallException::class);

    $container = [];
    $client = get_mock_client(200, [], null, $container);
    $client->get(new QR(['token' => 'my-token']));
});

it('throws BadMethodCallException on QR cancel', function () {
    $this->expectException(\BadMethodCallException::class);

    $container = [];
    $client = get_mock_client(200, [], null, $container);
    $client->cancel(new QR(['token' => 'my-token']));
});

it('throws ValidationException', function () {
    $payment = new Payment();
    $payment->id = '5D59DA1B1632424E874DDB219AD54597';
    $payment->amount = 100;
    $payment->currency = 'SEK';

    $container = [];
    $client = get_mock_client(422, [], '{
            "id": "5D59DA1B1632424E874DDB219AD54597",
            "payeePaymentReference": "0123456789",
            "paymentReference": "1E2FC19E5E5E4E18916609B7F8911C12",
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

it('throws ValidationException and returns single error', function () {
    $errorObject = [
        'errorCode' => 'ERR123',
        'errorMessage' => 'Something went wrong',
        'additionalInformation' => 'More info',
    ];
    $responseBody = json_encode($errorObject);

    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMock(ResponseInterface::class);
    $stream = $this->createMock(StreamInterface::class);
    $stream->method('getContents')->willReturn($responseBody);
    $response->method('getBody')->willReturn($stream);

    $exception = new ValidationException('Validation failed', $request, $response);
    $errors = $exception->getErrors();
    $this->assertCount(1, $errors);
    $this->assertInstanceOf(Error::class, $errors[0]);
    $this->assertEquals('ERR123', $errors[0]->errorCode);
    $this->assertEquals('Something went wrong', $errors[0]->errorMessage);
    $this->assertEquals('More info', $errors[0]->additionalInformation);
});

it('throws ValidationException and returns multiple errors', function () {
    $errorArray = [
        [
            'errorCode' => 'ERR1',
            'errorMessage' => 'First error',
            'additionalInformation' => 'Info1',
        ],
        [
            'errorCode' => 'ERR2',
            'errorMessage' => 'Second error',
            'additionalInformation' => 'Info2',
        ],
    ];
    $responseBody = json_encode($errorArray);

    $request = $this->createMock(RequestInterface::class);
    $response = $this->createMock(ResponseInterface::class);
    $stream = $this->createMock(StreamInterface::class);
    $stream->method('getContents')->willReturn($responseBody);
    $response->method('getBody')->willReturn($stream);

    $exception = new ValidationException('Validation failed', $request, $response);
    $errors = $exception->getErrors();
    $this->assertCount(2, $errors);
    $this->assertInstanceOf(Error::class, $errors[0]);
    $this->assertEquals('ERR1', $errors[0]->errorCode);
    $this->assertEquals('First error', $errors[0]->errorMessage);
    $this->assertEquals('Info1', $errors[0]->additionalInformation);
    $this->assertInstanceOf(Error::class, $errors[1]);
    $this->assertEquals('ERR2', $errors[1]->errorCode);
    $this->assertEquals('Second error', $errors[1]->errorMessage);
    $this->assertEquals('Info2', $errors[1]->additionalInformation);
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

it('throws CertificateDecodingException when hashing with empty signing certificate via Payout', function () {
    $this->expectException(CertificateDecodingException::class);

    $container = [];
    $payout = new Payout();
    $client = get_mock_client(200, [], null, $container, SigningCertificate::FALSE);
    $client->create($payout);
});

it('throws CertificateDecodingException when hashing with empty signing certificate', function () {
    $this->expectException(Olssonm\Swish\Exceptions\CertificateDecodingException::class);
    $payload = new ArrayObject(['foo' => 'bar']);
    \Olssonm\Swish\Util\Crypto::hashAndSign($payload, []);
});

it('throws CertificateDecodingException when fetching serial with bad signing certificate', function () {
    $this->expectException(CertificateDecodingException::class);

    $certificate = new Certificate(
        null, null, true, null
    );

    $certificate->getSerial();
});

it('throws CertificateDecodingException when signing with a bad certificate', function () {
    $this->expectException(Olssonm\Swish\Exceptions\CertificateDecodingException::class);
    $badCertPath = __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304_bad.pem';
    \Olssonm\Swish\Util\Crypto::sign('testhash', $badCertPath, null);
});

it('throws CertificateDecodingException when signing with an unreadable (empty) certificate', function () {
    $this->expectException(Olssonm\Swish\Exceptions\CertificateDecodingException::class);
    $emptyCertPath = __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304_empty.pem';
    \Olssonm\Swish\Util\Crypto::sign('testhash', $emptyCertPath, null);
});
