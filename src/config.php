<?php

return [
    'certificates' => [
        'private' => env('SWISH_CLIENT_CERTIFICATE_PATH'),
        'password' => env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
        'root' => env('SWISH_ROOT_CERTIFICATE_PATH', true),
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
