<?php

namespace Olssonm\Swish\Test;

uses(TestCase::class)->in(__DIR__);

uses()->beforeEach(function () {
    config()->set('swish.certificates', [
        __DIR__ . '/certificates/root.pem',
        __DIR__ . '/certificates/key.pem',
        'swish',
    ]);
})->in(__DIR__);
