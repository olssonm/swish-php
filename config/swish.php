<?php

return [
    'certificates' => [
        'client' => env('SWISH_CLIENT_CERTIFICATE_PATH'),
        'password' => env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
        'root' => env('SWISH_ROOT_CERTIFICATE_PATH', true),
        'signing' => env('SWISH_SIGNING_CERTIFICATE_PATH'), // Optional, used for payouts
        'signing_password' => env('SWISH_SIGNING_CERTIFICATE_PASSWORD'), // Optional, used for payouts
    ],
    'endpoint' => env('SWISH_URL', \Olssonm\Swish\Client::PRODUCTION_ENDPOINT),
];
