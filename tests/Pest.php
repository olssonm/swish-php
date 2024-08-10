<?php

namespace Olssonm\Swish\Test;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    config()->set('swish.certificates', [
        'client' => __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'password' => 'swish',
        'root' => __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        'signing' => __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304.pem',
        'signing_password' => 'swish',
    ]);
})->in(__DIR__);
