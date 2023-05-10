# Upgrade guide

## v1.0 - v2.0

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

In v2.0 you need to work with the Olssonm\Swish\Certificate which accepts three arguments; the root-certificate, client certificate and the passphrase, and use that object for the client:

```php
$certificate = new Certificate(
    '/path/to/root.pem', 
    '/path/to/client.pem', 
    'client-passphrase'
);
$client = new Client($certificate, $endpoint = Client::TEST_ENDPOINT)
```

All other methods are intact.
