<?php

return [
    'certificates' => [
        'client' => env('SWISH_CLIENT_CERTIFICATE_PATH'),
        'password' => env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
        'root' => env('SWISH_ROOT_CERTIFICATE_PATH', true),
        'signing' => env('SWISH_SIGNING_CERTIFICATE_PATH', null), // Optional, used for payouts
        'signing_password' => env('SWISH_SIGNING_CERTIFICATE_PASSWORD', null), // Optional, used for payouts
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
