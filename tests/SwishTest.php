<?php

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Client;
use Olssonm\Swish\Payment;
use Olssonm\Swish\PaymentResult;
use Olssonm\Swish\Providers\SwishServiceProvider;
use Olssonm\Swish\Refund;
use Olssonm\Swish\RefundResult;
use Olssonm\Swish\SwishObject;

it('loads package', function () {
    $providers = $this->app->getLoadedProviders();
    $this->assertTrue(array_key_exists(SwishServiceProvider::class, $providers));
});

it('loads facade', function () {
    $facade = $this->app['swish'];
    $this->assertTrue(is_a($facade, Client::class));
});

it('can work with Payment-object', function() {
    $payment = new Payment();
    $payment->id = 123;

    $this->assertEquals(123, $payment->id);
    $this->assertEquals(['id' => 123], $payment->toArray());
    $this->assertEquals('{"id":123}', json_encode($payment));
});

it('can work with Refund-object', function () {
    $payment = new Refund();
    $payment->id = 456;

    $this->assertEquals(456, $payment->id);
    $this->assertEquals(['id' => 456], $payment->toArray());
    $this->assertEquals('{"id":456}', json_encode($payment));
});

it('can work with generic SwishObject', function() {
    $swishObject = new SwishObject([0 => 'foo', 1 => 'bar']);
    $swishObject->offsetSet(0, 'test');
    $swishObject->offsetUnset(1);

    $this->assertTrue($swishObject->offsetExists(0));
    $this->assertTrue(isset($swishObject[0]));
    $this->assertFalse(isset($swishObject[1]));
    $this->assertEquals(1, count($swishObject));
    $this->assertEquals('test', $swishObject->offsetGet(0));
});

it('can fetch payment', function() {
    $payment = new Payment(['id' => 123]);

    $container = [];
    $mock = new MockHandler([
        new Response(200, [],
            '{
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
            }'),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    $client = new Client([], Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'base_uri' => Client::TEST_ENDPOINT,
    ]));

    $response = $client->get($payment);

    $this->assertEquals(200, $container[0]['response']->getStatusCode());
    $this->assertEquals('/payments/123', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('GET', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('PAID', $response->status);
    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $response->id);
});

it('can make payment', function() {
    $payment = new Payment();
    $payment->id = 123;
    $payment->amount = 100;
    $payment->currency = 'SEK';
    $payment->payee = '123456789';

    $container = [];
    $mock = new MockHandler([
        new Response(201, [
            'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088',
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
    $this->assertEquals('/payments', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());

    $this->assertInstanceOf(PaymentResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088', $response->location);
    $this->assertEquals('11A86BE70EA346E4B1C39C874173F088', $response->id);
    $this->assertEquals('my-token', $response->paymentRequestToken);
});

it('can make refund', function() {
    $refund = new Refund;
    $refund->id = 123;
    $refund->amount = 100;
    $refund->currency = 'SEK';
    $refund->payerAlias = '1231181189';
    $refund->message = 'Refund for Kingston USB Flash Drive 8 GB';

    $container = [];
    $mock = new MockHandler([
        new Response(201, [
            'location' => 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088',
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

    $response = $client->refund($refund);

    $this->assertEquals(201, $container[0]['response']->getStatusCode());
    $this->assertEquals('/refund', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PUT', $container[0]['request']->getMethod());

    $this->assertInstanceOf(RefundResult::class, $response);

    $this->assertEquals('https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088', $response->location);
    $this->assertEquals('11A86BE70EA346E4B1C39C874173F088', $response->id);
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
    $this->assertEquals('/paymentrequests/5D59DA1B1632424E874DDB219AD54597', $container[0]['request']->getUri()->getPath());
    $this->assertEquals('PATCH', $container[0]['request']->getMethod());

    $this->assertInstanceOf(Payment::class, $response);

    $this->assertEquals('5D59DA1B1632424E874DDB219AD54597', $response->id);
    $this->assertEquals('CANCELLED', $response->status);
});
