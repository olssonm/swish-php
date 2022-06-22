<?php

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Client;
use Olssonm\Swish\Payment;
use Olssonm\Swish\Providers\SwishServiceProvider;
use Olssonm\Swish\Refund;

// it('loads package', function () {
//     $providers = $this->app->getLoadedProviders();
//     $this->assertTrue(array_key_exists(SwishServiceProvider::class, $providers));
// });

// it('loads facade', function () {
//     $facade = $this->app['swish'];
//     $this->assertTrue(is_a($facade, Client::class));
// });

// it('can work with Payment-object', function() {
//     $payment = new Payment();
//     $payment->id = 123;

//     $this->assertEquals(123, $payment->id);
//     $this->assertEquals(['id' => 123], $payment->toArray());
//     $this->assertEquals('{"id":123}', json_encode($payment));
// });

// it('can work with Refund-object', function () {
//     $payment = new Refund();
//     $payment->id = 456;

//     $this->assertEquals(456, $payment->id);
//     $this->assertEquals(['id' => 456], $payment->toArray());
//     $this->assertEquals('{"id":456}', json_encode($payment));
// });

it('can make payment request', function() {
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
    dd($response);

    $this->assertEquals(201, $response->getStatusCode());
});
