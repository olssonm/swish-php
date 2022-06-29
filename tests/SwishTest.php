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

beforeEach(function () {
    $this->certificate = [
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.p12',
        'swish'
    ];
});

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

it('can work with Payment-object', function() {
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

it('can work with generic Resource', function() {
    $resource = new Resource([0 => 'foo', 1 => 'bar']);
    $resource->offsetSet(0, 'test');
    $resource->offsetUnset(1);

    $this->assertTrue($resource->offsetExists(0));
    $this->assertTrue(isset($resource[0]));
    $this->assertFalse(isset($resource[1]));
    $this->assertEquals(1, count($resource));
    $this->assertEquals('test', $resource->offsetGet(0));
});

it('can fetch payment', function() {
    $id = UUID::make();
    $payment = new Payment([
        'id' => $id
    ]);

    $container = [];
    $mock = new MockHandler([
        new Response(200, [],
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
            }'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client($this->certificate, Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'curl' => [
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 20,
            'cert' => $this->certificate
        ],
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $response = $client->get($payment);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/paymentrequests/' . $payment->id, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('GET', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('PAID', $response->status);
    $this->assertEquals($id, $response->id);
});

it('can make payment', function() {
    $id = UUID::make();
    $payment = new Payment();
    $payment->id = $id;
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payee = '123456789';
    $payment->payeeAlias = '1234679304';
    $payment->callbackUrl = 'https://example.com';

    $container = [];
    $mock = new MockHandler([
        new Response(201, [
            'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/'. $id,
            'paymentrequesttoken' => 'my-token',
        ], null),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $response = $client->create($payment);

    $this->assertEquals(201, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v2/paymentrequests/' . $id, $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());

    $this->assertInstanceOf(PaymentResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id, $response->location);
    $this->assertEquals($id, $response->id);
    $this->assertEquals('my-token', $response->paymentRequestToken);
});

it('can make refund', function() {
    $id = Uuid::make();
    $refund = new Refund;
    $refund->id = $id;
    $refund->amount = 100;
    $refund->currency = 'SEK';
    $refund->payerAlias = '1231181189';
    $refund->message = 'Refund for Kingston USB Flash Drive 8 GB';

    $container = [];
    $mock = new MockHandler([
        new Response(201, [
            'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/' . $id,
            'paymentrequesttoken' => 'my-token',
        ], null),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

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
    $mock = new MockHandler([
        new Response(200, [],
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
            }'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $response = $client->cancel($payment);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/swish-cpcapi/api/v1/paymentrequests/5D59DA1B1632424E874DDB219AD54597', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PATCH', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $response->id);
    $this->assertEquals('CANCELLED', $response->status);
});

it('can handle callback', function() {
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

it('throws ValidationException', function () {
    $payment = new Payment();
    $payment->id = '5D59DA1B1632424E874DDB219AD54597';
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payee = '123456789';

    $container = [];
    $mock = new MockHandler([
        new Response(422, [], '{
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
        }'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

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

it('throws ClientException', function() {
    $this->expectException(ClientException::class);

    $mock = new MockHandler([
        new Response(429, [], null),
    ]);
    $stack = HandlerStack::create($mock);

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $client->create(new Payment());
});

it('throws ServerException', function () {
    $this->expectException(ServerException::class);

    $mock = new MockHandler([
        new Response(500, [], null),
    ]);
    $stack = HandlerStack::create($mock);

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'cert' => [
            __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.p12',
            'swish'
        ],
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $client->create(new Payment());
});

it('throws CallbackDecodingException', function() {
    $this->expectException(CallbackDecodingException::class);
    Callback::parse('invalid');
});
