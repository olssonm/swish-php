# Swish PHP

[![Supported PHP-versions](https://img.shields.io/packagist/php-v/olssonm/swish-php?style=flat-square)](https://packagist.org/packages/olssonm/swish-php)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/olssonm/swish-php.svg?style=flat-square)](https://packagist.org/packages/olssonm/swish-php)
[![Build Status](https://img.shields.io/github/actions/workflow/status/olssonm/swish-php/test.yaml?branch=main&style=flat-square)](https://github.com/olssonm/swish-php/actions?query=workflow%3A%22Run+tests%22)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A simple and easy to use wrapper for the Swish-API in PHP. Also includes providers and facades for quick setup with Laravel.

## Prerequisites

This package supports PHP ^8.1. Tested against Laravel 10 & 11. PHP needs to be compiled with the cURL and SSL extensions (in an absolute majority of cases, they should be available by default).

*Using an older version of PHP or Laravel? Check out v1 and v2 of this package.*

## Installation

```
composer require olssonm/swish-php
```

## Setup

You will need to have access to your Swish-certificates to use this package in production. You can however use their testing/Merchant Swish Similator-environment without being a Swish-customer during development.

Read more about testing in their MSS-environment in their [official documentation](https://developer.swish.nu/documentation/environments#merchant-swish-simulator). A quick rundown on using/creating Swish-certificates [is published here](https://marcusolsson.me/artiklar/hur-man-skapar-certifikat-for-swish) (in Swedish).

When creating the client, you will have to set which environment you are working with (otherwise it defaults to the production environment, `https://cpc.getswish.net/swish-cpcapi/api/`), you may use any of the following options:

``` php
Client::TEST_ENDPOINT // https://mss.cpc.getswish.net/swish-cpcapi/api/
Client::PRODUCTION_ENDPOINT // https://cpc.getswish.net/swish-cpcapi/api/
Client::SANDBOX_ENDPOINT // https://staging.getswish.pub.tds.tieto.com/swish-cpcapi/api/
```

``` php
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

$certificate = new Certificate( 
    '/path/to/client.pem', 
    'client-passphrase',
    '/path/to/root.pem', // Can also be omitted for "true" to verify peer
    '/path/to/signing.key', // Path to signing certificate, only used for payouts
    'signing-passphrase' // Only used for payouts
);
$client = new Client($certificate, $endpoint = Client::TEST_ENDPOINT)
```

> [!IMPORTANT]  
> The paths to the certificates should be absolute. You can use `realpath -s YOUR_CERT.pem` for this. 

### Laravel

With the Laravel service provider and facades you can work with the package more eloquently. Just require the package and publish the configuration:

```
php artisan vendor:publish --provider="Olssonm\Swish\Providers\SwishServiceProvider"
```

In `/config/swish.php`, you can then set your details accordingly:

``` php
return [
    'certificates' => [
        'client' => env('SWISH_CLIENT_CERTIFICATE_PATH'),
        'password' => env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
        'root' => env('SWISH_ROOT_CERTIFICATE_PATH', true),
        'signing' => env('SWISH_SIGNING_CERTIFICATE_PATH', null),
        'signing_password' => env('SWISH_CLIENT_SIGNING_PASSWORD', null),
    ],
    'endpoint' => env('SWISH_URL', \Olssonm\Swish\Client::PRODUCTION_ENDPOINT),
];
```

This may also be a good place to keep you payee-alias, callback-url and such, which you can then access with `config('swish.payee_alias)` etc.

It's recommended to store certificates in the `storage/app/private` directory, which is protected by default. Provide relative paths, as they will be automatically resolved from your Laravel application's `storage/app/private` directory.

## Usage

A typical case for creating a Swish-payment.

``` php
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;
use Olssonm\Swish\Payment;

$certificate = new Certificate(
    '/path/to/client.pem', 
    'client-passphrase'
);
$client = new Client($certificate);

// Create a new payment-object
$payment = new Payment([
    'callbackUrl' => 'https://callback.url',
    'payeePaymentReference' => 'XVY77',
    'payeeAlias' => '123xxxxx',
    'payerAlias' => '321xxxxx',
    'amount' => '100',
    'currency' => 'SEK',
    'message' => 'A purchase of my product',
]);

// Perform the request
$response = $client->create($payment);

// $response->id = '11A86BE70EA346E4B1C39C874173F088'
// $response->location = 'https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088'
// $response->paymentRequestToken = 'a-unique-token'

```

With Laravel you can also use the facade and save a few lines of code (in this example `Olssonm\Swish\Facades\Swish` has been aliased to `Swish`)

```php
use Swish;
use Olssonm\Swish\Payment;

$response = Swish::create(new Payment([
    'callbackUrl' => 'https://callback-url.com',
    'payeePaymentReference' => 'XVY77',
    'payeeAlias' => '123xxxxx',
    'payerAlias' => '321xxxxx',
    'amount' => '100',
    'currency' => 'SEK',
    'message' => 'My product',
]));
```

### Payments and Refunds

> [!TIP]
> Read more about [payments](https://developer.swish.nu/api/payment-request/v2) and [refunds](https://developer.swish.nu/api/refunds/v2) in the official documentation

Always when using the client, use the Payment and Refund-classes <u>even if only the ID is needed for the action</u>, i.e:

``` php
$payment = $client->get(Payment(['id' => '5D59DA1B1632424E874DDB219AD54597']));
```

### Payouts

> [!TIP]
> Read more about [payouts](https://developer.swish.nu/api/payouts/v1) in the official documentation

Payouts need to be hashed using SHA512 and signed with a signing certificate before being sent to Swish – don't worry though, this package will handle most of this automatically. Just make sure that the path to your signing certificate is set:

``` php
$certificate = new Certificate(
    '/path/to/client.pem', 
    'client-passphrase',
    true,
    '/path/to/signing.key',
    'signing-passphrase'
);
$client = new Client($certificate);

$payout = $client->create(new Payout([]));
```

Additionally your certificate's (*note:* your signing certificate, not your client certificate) serial needs to be supplied. You can either use the `Certificate`-class to handle on the fly:

``` php
$certificate = new Certificate(/**/);
$payout = new Payout([
    'signingCertificateSerialNumber' => $certificate->getSerial()
])
```

Or assign it yourself ([see gist for extracting serial](https://gist.github.com/olssonm/7da7d35dff2ec3ae74d0d0439003913c)):

``` php
$payout = new Payout([
    'signingCertificateSerialNumber' => '4512B3EBDA6E3CE6BFB14ABA6274A02C'
])
```

> [!IMPORTANT]  
> Note that Payouts uses `payoutInstructionUUID` instead of an `ID`, you should [set this yourself to keep track of it](#regarding-idsuuids). If it's missing, it will be set automatically upon creation.

### Regarding IDs/UUIDs

This package uses the v2 of the Swish API where a UUID is set by the merchant. This package handles all these aspects automatically as needed, you may however choose to manually set the ID/instructionUUID (either in Swish's own format, or a default v4-format):

``` php
$id = 'EBB5C73503084E3C9AEA8A270AEBFE15';
// or
$id = 'ebb5c735-0308-4e3c-9aea-8a270aebfe15';

$payment = new Payment([
    'id' => $id
]);
```

When generating the UUIDs on the fly, the package uses [Ramsey/Uuid](https://github.com/ramsey/uuid) to generate RFC4122 (v4) UUIDs. Swish accepts V1, 3, 4 and 5 UUIDs if you chose to set your own UUIDs.

If an invalid UUID is used, a `Olssonm\Swish\Exceptions\InvalidUuidException` will be thrown.

> [!NOTE] 
> No matter if you set your own UUID och let the package handle the generation, the UUID will <u>always</u> be formatted for Swish automatically (dashes removed and in uppercase). 

### Available methods

This package handles the most common Swish-related tasks; retrieve, make and cancel payments. Retrieve and create payouts, aswell as refunds can be created and retrieved. All of them are performed via `Olssonm\Swish\Client`;

``` php
$client->get(Payment $payment | Refund $refund | Payout $payout);  
$client->create(Payment $payment | Refund $refund | Payout $payout);  
$client->cancel(Payment $payment);
```

### Exception-handling

When encountering a validation-error an `Olssonm\Swish\Exceptions\ValidationException` will be thrown. The Object will contain both the request, response as well as the `getErrorCode()` and 
`getErrorMessage()`-helpers.

```php
try {
    $response = $client->create($payment);
} catch (ValidationException $exception) {
    $errors = $exception->getErrors();
    foreach ($errors as $error) {
        $error->errorCode;
        // AM03
        $error->errorMessage;
        // Invalid or missing Currency.
    }
}
```

For `4xx`-error a `\Olssonm\Swish\Exceptions\ClientException` will be thrown, and for `5xx`-errors `\Olssonm\Swish\Exceptions\ServerException`. Both of these implements Guzzles `BadResponseException` which makes the request- and response-objects available if needed.

## Callbacks

Swish recommends to not use the `payments`-endpoint to get the status of a payment or refund (even if they themselves use it in some of their examples...), but instead use callbacks.

This package includes a simple helper to retrieve a `Payment`, `Refund` or `Payout` object from a callback that will contain all data from Swish:

```php 
use Olssonm\Swish\Callback;

$paymentOrRefund = Callback::parse();

// get_class($paymentOrRefund) = \Olssonm\Swish\Payment::class, \Olssonm\Swish\Refund::class, \Olssonm\Swish\Payout::class
```

The helper automatically retrieve the current HTTP-request (via `file_get_contents('php://input')`). You may however inject your own data if needed (or if you for example has a Laravel request-object ready):

```php
class SwishController 
{
    public function Callback(Request $request)
    {
        $data = Callback::parse($request->getContent());

        if(get_class($data) == \Olssonm\Swish\Payment::class) {
            // Handle payment callback
        } else if(get_class($data) == \Olssonm\Swish\Refund::class) {
            // Handle refund callback
        } else if(get_class($data) == \Olssonm\Swish\Payout::class) {
            // Handle payout callback
        }
    }
}
```

*In a real world scenario you probably want to use separate callback-urls for your refunds, payouts and payments to prevent unnecessary parsing as the example above.*

> [!CAUTION]
> Please note that the callback from Swish is not encrypted or encoded in any way , instead you should make sure that the callback is coming from a [valid IP-range](https://developer.swish.nu/documentation/environments). 

## License

The MIT License (MIT). Please see the [LICENSE](LICENSE) for more information.

© 2022-2024 [Marcus Olsson](https://marcusolsson.me).
