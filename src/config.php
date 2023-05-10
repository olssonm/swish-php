<?php

return [
    'certificates' => [
        env('SWISH_ROOT_CERTIFICATE_PATH'),
        env('SWISH_CLIENT_CERTIFICATE_PATH'),
        env('SWISH_CLIENT_CERTIFICATE_PASSWORD'),
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
