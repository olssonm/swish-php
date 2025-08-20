<?php

use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;
use Olssonm\Swish\Providers\SwishServiceProvider;
use Olssonm\Swish\Resource;
use Olssonm\Swish\Facades\Swish;
use Olssonm\Swish\Util\Uuid;

it('loads package', function () {
    $providers = $this->app->getLoadedProviders();
    $this->assertTrue(array_key_exists(SwishServiceProvider::class, $providers));
});

it('loads facade', function () {
    $facade = $this->app['swish'];
    $this->assertTrue(is_a($facade, Client::class));

});

it('resolves facade', function () {
    $this->assertInstanceOf(Client::class, Swish::getFacadeRoot());
});

it('loads config', function () {
    $this->assertArrayHasKey('swish', config());
    $this->assertArrayHasKey('client', config('swish.certificates'));
    $this->assertArrayHasKey('password', config('swish.certificates'));
    $this->assertArrayHasKey('root', config('swish.certificates'));
    $this->assertArrayHasKey('signing', config('swish.certificates'));
    $this->assertArrayHasKey('signing_password', config('swish.certificates'));
    $this->assertArrayHasKey('endpoint', config('swish'));
});

test('can generate uuids', function () {
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

it('can retrieve certificate serial', function () {
    $certificate = new Certificate(
        __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
        'swish',
        __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        __DIR__ . '/certificates/Swish_Merchant_TestSigningCertificate_1234679304.pem',
    );

    $this->assertEquals($certificate->getSerial(), '5E24D8820F5B62C7E5CAC75D20D6E754');
});
