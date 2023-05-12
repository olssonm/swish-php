<?php

namespace Olssonm\Swish\Test;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    config()->set('swish.certificates', [
        __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'swish',
    ]);
})->in(__DIR__);
