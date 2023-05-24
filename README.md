# Swish PHP

[![Supported PHP-versions](https://img.shields.io/packagist/php-v/olssonm/swish-php?style=flat-square)](https://packagist.org/packages/olssonm/swish-php)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/olssonm/swish-php.svg?style=flat-square)](https://packagist.org/packages/olssonm/swish-php)
[![Build Status](https://img.shields.io/github/actions/workflow/status/olssonm/swish-php/test.yml?branch=main&style=flat-square)](https://github.com/olssonm/swish-php/actions?query=workflow%3A%22Run+tests%22)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

A simple and easy to use wrapper for the Swish-API in PHP. Also includes providers and facades for quick setup with Laravel.

## Prerequisites

This package supports PHP ^8.1, as well as Laravel 7 and up to the latest version. PHP needs to be compiled with the cURL and SSL-extensions (in an abosolute majority of cases they should be available per default.)

*Using PHP 7.4 or 8.0? v1.0 has support for these.*

## Installation

```
composer require olssonm/swish-php
```

## Setup

You will need to have access to your Swish-certificates to use this package in production. You can however use their testing/Merchant Swish Similator-environment without being a Swish-customer during development.

Read more about testing in their MSS-environment in their [official documentation](https://developer.swish.nu/documentation/environments#:~:text=the%20certificate%20again.-,Merchant%20Swish%20Simulator,-The%20Swish%20server). A quick rundown on using/creating Swish-certificates [is published here](https://marcusolsson.me/artiklar/hur-man-skapar-certifikat-for-swish) (in Swedish).

When creating the client you will have to set which environment you are working with (otherwise it defaults to production-environment, `https://cpc.getswish.net/swish-cpcapi/api/v2`), you may use `Client::TEST_ENDPOINT` and `Client::PRODUCTION_ENDPOINT` for this:

``` php
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

$certificate = new Certificate( 
    '/path/to/client.pem', 
    'client-passphrase',
    '/path/to/root.pem' // Can also be omitted for "true" to verify peer
);
$client = new Client($certificate, $endpoint = Client::TEST_ENDPOINT)
```

### Laravel

With the Laravel service provider and facades you can work with the package more eloquently. Just require the package and publish the configuration:

```
php artisan vendor:publish --provider="Olssonm\Swish\Providers\SwishServiceProvider"
```

In `/config/swish.php` you can then set your details accordingly:

``` php
return [
    'certificates' => [
        env('SWISH_ROOT_CERTIFICATE_PATH'),
        env('SWISH_CLIENT_CERTIFICATE_PATH'),
        env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
```

This may also be a good place to keep you payee-alias, callback-url and such, which you can then access with `config('swish.payee_alias)` etc.

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

Always when using the client, use the Payment and Refund-classes <u>even if only the ID is needed for the action</u>, i.e:

``` php
$payment = $client->get(Payment(['id' => '5D59DA1B1632424E874DDB219AD54597']));
```

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

If an invalid UUID is used, a `Olssonm\Swish\Exceptions\InvalidUuidException` will be thrown.

*Note 1:* Wheter you set a default UUID or one in the Swish-format – it will <u>always</u> be formatted for Swish automatically (dashes removed and in uppercase).  
*Note 2:* This package uses [Ramsey/Uuid](https://github.com/ramsey/uuid) to generate RFC4122 (v4) UUIDs on the fly. Swish accepts V1, 3, 4 and 5 UUIDs if you chose to set your own UUIDs.

### Available methods

This package handles the most common Swish-related tasks; retrieve, make and cancel payments, as well as retrieve and make refunds. All of them are performed via `Olssonm\Swish\Client`;

`$client->get(Payment $payment | Refund $refund);`  
`$client->create(Payment $payment | Refund $refund);`  
`$client->cancel(Payment $payment);`

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

## Callback

Swish recommends to not use the `payments`-endpoint to get the status of a payment or refund (even if they themselves use it in some of their examples...), but instead use callbacks.

This package includes a simple helper to retrieve a `Payment` or `Refund` object from a callback that will contain all data from Swish:

```php 
use Olssonm\Swish\Callback;

$paymentOrRefund = Callback::parse($content = null);

// get_class($paymentOrRefund) = \Olssonm\Swish\Payment::class or \Olssonm\Swish\Refund::class
```

The helper automatically retrieve the current HTTP-request. You may however inject your own data if needed (or if you for example has a Laravel request-object ready):

```php
class SwishController 
{
    public function Callback(Request $request)
    {
        $data = Callback::parse($content = $request->getContent());

        if(get_class($data) == \Olssonm\Swish\Payment::class) {
            // Handle payment callback
        } else if(get_class($data) == \Olssonm\Swish\Refund::class) {
            // Handle refund callback
        }
    }
}
```

*Note: in a real world scenario you probably want to use separate callback-urls for your refunds and payments to prevent unnecessary parsing as the example above* 

Please note that the callback from Swish is not encrypted or encoded in any way, instead you should make sure that the callback is coming from a [valid IP-range](https://developer.swish.nu/documentation/environments). 

## License

The MIT License (MIT). Please see the [LICENSE](LICENSE) for more information.

© 2022-2023 [Marcus Olsson](https://marcusolsson.me).
