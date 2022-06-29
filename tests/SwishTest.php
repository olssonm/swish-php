<?php

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Callback;
use Olssonm\Swish\Client;
use Olssonm\Swish\Exceptions\CallbackDecodingException;
use Olssonm\Swish\Exceptions\ClientException;
use Olssonm\Swish\Exceptions\InvalidUuidException;
use Olssonm\Swish\Exceptions\ServerException;
use Olssonm\Swish\Exceptions\ValidationException;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Providers\SwishServiceProvider;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Olssonm\Swish\Resource;
use Olssonm\Swish\Util\Uuid;

it('loads package', function () {
    $providers = $this->app->getLoadedProviders();
    $this->assertTrue(array_key_exists(SwishServiceProvider::class, $providers));
});

it('loads facade', function () {
    $facade = $this->app['swish'];
    $this->assertTrue(is_a($facade, Client::class));
});

it('can generate uuids', function () {
    $SwishUuid = Uuid::make();
    $this->assertEquals(32, strlen($SwishUuid));

    $defaultUuid = Uuid::make(Uuid::DEFAULT);
    $this->assertEquals(36, strlen($defaultUuid));
});

it('can validate uuids', function () {
    $SwishUuid = Uuid::make();
    $this->assertTrue(Uuid::validate($SwishUuid));

    $defaultUuid = Uuid::make(Uuid::DEFAULT);
    $this->assertTrue(Uuid::validate($defaultUuid));

    $fakeUuid = 'abc123';
    $this->assertFalse(Uuid::validate($fakeUuid));
});

it('can work with Payment-object', function () {
    $id = UUID::make();
    $payment = new Payment([
        'id' => $id
    ]);

    $this->assertEquals($id, $payment->id);
    $this->assertEquals(['id' => $id], $payment->toArray());
    $this->assertEquals('{"id":"' . $id . '"}', json_encode($payment));
});

it('can work with Refund-object', function () {
    $id = UUID::make();
    $refund = new Refund([
        'id' => $id
    ]);

    $this->assertEquals($id, $refund->id);
    $this->assertEquals(['id' => $id], $refund->toArray());
    $this->assertEquals('{"id":"' . $id . '"}', json_encode($refund));
});

it('can work with generic Resource', function () {
    $resource = new Resource([0 => 'foo', 1 => 'bar']);
    $resource->offsetSet(0, 'test');
    $resource->offsetUnset(1);

    $this->assertTrue($resource->offsetExists(0));
    $this->assertTrue(isset($resource[0]));
    $this->assertFalse(isset($resource[1]));
    $this->assertEquals(1, count($resource));
    $this->assertEquals('test', $resource->offsetGet(0));
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

it('can handle callback', function () {
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

it('throws CallbackDecodingException', function () {
    $this->expectException(CallbackDecodingException::class);
    Callback::parse('invalid');
});

// Make a full standard test against the MSS API
test('full chain of requests', function () {

    $client = get_real_client();

    // New payment
    $payment = new Payment([
        'amount' => 100,
        'paymentRefeference' => 'abc123',
        'currency' => 'SEK',
        'payee' => '123456789',
        'message' => 'Kingston USB Flash Drive 8 GB',
        'callbackUrl' => 'https://webhook.site/ee23acc8-0ad9-4c34-866e-ef8ef421d7d4',
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
        'callbackUrl' => 'https://webhook.site/ee23acc8-0ad9-4c34-866e-ef8ef421d7d4',
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

    // Cancel a payment
    $payment = new Payment(['id' => $id]);
    $client->cancel($payment);
});

function get_mock_client($code, $expectedHeaders, $expectedBody, &$history)
{
    $mock = new MockHandler([
        new Response($code, $expectedHeaders, $expectedBody),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    return new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'curl' => [
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 20,
            'cert' => [
                __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.p12',
                'swish'
            ]
        ],
        'base_uri' => Client::TEST_ENDPOINT,
    ]));
}

function get_real_client()
{
    return new Client([
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.p12',
        'swish'
    ], Client::TEST_ENDPOINT);
}
