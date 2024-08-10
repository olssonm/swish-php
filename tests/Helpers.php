<?php

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

function get_real_client($certificate = null)
{
    if (!$certificate) {
        $certificate = new Certificate(
            __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
            'swish',
            __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
            __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304.key',
        );
    }
    return new Client($certificate, Client::TEST_ENDPOINT);
}

function get_mock_client($code, $expectedHeaders, $expectedBody, &$history, $signing = true)
{
    $mock = new MockHandler([
        new Response($code, $expectedHeaders, $expectedBody),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($history));

    $certificate = null;

    if ($signing) {
        $certificate = new Certificate(
            __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
            'swish',
            __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
            __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304.key',
        );
    } else {
        $certificate = new Certificate(
            __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
            'swish',
            __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        );
    }


    return new Client($certificate, Client::TEST_ENDPOINT, new GuzzleHttpClient([
        'handler' => $stack,
        'http_errors' => false,
        'curl' => [
            CURLOPT_TCP_KEEPALIVE => 1,
            CURLOPT_TCP_KEEPIDLE => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 20,
            'verify' => __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
            'cert' => [
                __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
                'swish'
            ]
        ],
        'base_uri' => Client::TEST_ENDPOINT,
    ]));
}
