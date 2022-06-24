# Swish PHP

Simple and easy to use Swish-wrapper for PHP. Also includes providers and facades for easy usage with Laravel.

## Installation

```
composer require olssonm/swish-php
```

## Setup

You will need to have access to your Swish-certificates to use this package in production. You can however use their testing/MSS-environment without being a Swish-customer during development.

Certificates and documentation for testing is available here. A quick rundown on using/creating Swish-certificates [is available here](https://marcusolsson.me/artiklar/hur-man-skapar-certifikat-for-swish) (in Swedish).

When creating the client you will have to set wich environemnt you are working with (otherwise it defaults to production-environment, `https://cpc.getswish.net/swish-cpcapi/api/v2`), you may use `Client::TEST_ENDPOINT` and `Client::PRODUCTION_ENDPOINT` for this:

``` php
$certificates = [
    '/path/to/my/certificate.p12',
    'my-certificate-password'
];
$client = new Client($certificates, $endpoint = Client::TEST_ENDPOINT)
```

### Laravel

With the Laravel service provider and facades you can work with the package more eloquently. Just require the package and publish the configuration:

```
php artisan vendor:public --provider="\Olssonm\Swish\Providers\SwishServiceProvider"
```

In `/config/swish.php` you can then set your details accordingly:

``` php
return [
    'certificates' => [
        env('SWISH_CLIENT_CERTIFICATE'),
        env('SWISH_CLIENT_CERTIFICATE_PASSWORD')
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
```

## Usage

A typical case for creating a Swish-payment.

``` php
use Olssonm\Swish\Client;
use Olssonm\Swish\Payment;

$certificates = [
    '/path/to/my/certificate.p12',
    'my-certificate-password'
];
$client = new Client($certificates);

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

// $response->id = 11A86BE70EA346E4B1C39C874173F088
// $response->location = https://mss.cpc.getswish.net/swish-cpcapi/api/v1/paymentrequests/11A86BE70EA346E4B1C39C874173F088
// $response->paymentRequestToken = 'a-unique-token'

```

With Laravel you can also use the facade and save a few lines of code (with this example `Olssonm\Swish\Facades\Swish` has been aliased to `Swish`)

```php
use Swish;
use Olssonm\Swish\Payment;

$response = Swish::create(new Payment([
    'callbackUrl' => 'https://callback-url.com',
    'payeePaymentReference' => 'XVY77",
    'payeeAlias' => 123xxxxx,
    'payerAlias' => 321xxxxx,
    'amount' => 100,
    'currency' => 'SEK',
    'message' => 'My product',
]));
```

### Available methods

This package handles the most common Swish-related tasks; retrieve, make and cancel payments, as well as retrieve and make refunds. All of them are performed via `Olssonm\Swish\Client`;

`$client->get(\Olssonm\Swish\Payment $payment);`  
`$client->create(\Olssonm\Swish\Payment $payment);`  
`$client->cancel(\Olssonm\Swish\Payment $payment);`  
`$client->refund(\Olssonm\Swish\Refund $refund);`

## Callback

Swish recommends to not use the `payments`-endpoint to get the status of a payment or refund (even if they themselves use it in their examples...), but instead use callbacks.

This package includes a simple helper to retrieve a `Payment` or `Refund` object from a callback that will contain all data from Swish:

```php 
use Olssonm\Swish\Callback;

$paymentOrRefund = Callback::parse($content = null);

// get_class($paymentOrRefund) = \Olssonm\Swish\Payment::class or \Olssonm\Swish\Refund::class
```

The helper automatically retrieve the current HTTP-request. You may however inject your own data if needed (or if you have a Laravel request-object ready):

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

*Note: in a real world scenario you probably want to use seperate callback-urls for your refunds and payments to prevent unnecessary parsing as the example above* 
