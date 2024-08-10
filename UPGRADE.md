# Upgrade guide

## v2.x - v3.0

### Overview

v3.0 introduces the ability to perform payouts via the Swish-API. To enable this, a signing certificate has to be used, and therefore the usage of the `Olssonm\Swish\Certificate`-class been somewhat altered.

Additionally, the `Olssonm\Swish\Util\Crypto` and `Olssonm\Swish\Util\Time` utilities has been added to enable the necessary signing of payloads for the payouts.

All other methods for creating and retrieving payments and refunds are intact.

### Changes

`Olssonm\Swish\Certificate` now accepts an additional parameter – the path to your signing certificate:

``` php
$certificate = new Certificate( 
    '/path/to/client.pem', 
    'client-passphrase',
    '/path/to/root.pem', 
    '/path/to/signing.key' // <-- New
);
```

Please note that it can be omitted if you are not planning to use the Payout-features.

If you're using Laravel and publish the config, you should add the `signing`-key to the swish.php-config:

``` php
return [
    'certificates' => [
        /* ... */
        'signing' => env('SWISH_SIGNING_CERTIFICATE_PATH', null),
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
]
```

## v1.x - v2.0

### Overview
v2.0 uses pem-certificates to simplify the handling for the authorization chain with Swish.

- Added the Olssonm\Swish\Certificate-class
- Reworked necessary parameters for setting up the client
- Dropped support for PHP 7.4 and 8.0 which are now deprecated

### How-to

In v1.0 you uses your p12-certificate with your passphrase as arguments for the client:

```php
$certificates = [
    '/path/to/my/certificate.p12',
    'my-certificate-password'
];
$client = new Client($certificates, $endpoint = Client::TEST_ENDPOINT)
```

In v2.0 you need to work with the Olssonm\Swish\Certificate which accepts three arguments; client certificate and the passphrase and the root-certificate – then use that object for the client:

```php
$certificate = new Certificate(
    '/path/to/client.pem', 
    'client-passphrase',
    '/path/to/root.pem', // Can also be omitted for "true" to verify peer
);
$client = new Client($certificate, $endpoint = Client::TEST_ENDPOINT)
```

All other methods are intact.

#### Config

The config-array and associated env-parameters has been updated:

Each value now has their own seperate key:

```php
'certificates' => [
    'client' => env('SWISH_CLIENT_CERTIFICATE_PATH'),
    'password' => env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
    'root' => env('SWISH_ROOT_CERTIFICATE_PATH', true),
],
```

`SWISH_CLIENT_CERTIFICATE` is now `SWISH_CLIENT_CERTIFICATE_PATH`, and `SWISH_ROOT_CERTIFICATE_PATH` has been added (you will need to supply the Swish root certificate, or set to "true" to just verify the peer).
