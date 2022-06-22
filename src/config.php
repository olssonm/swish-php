<?php

return [
    'certificates' => [
        env('SWISH_CLIENT_CERTIFICATE'),
        env('SWISH_CLIENT_CERTIFICATE_PASSWORD')
    ],
    'endpoint' => \Olssonm\Swish\Client::PRODUCTION_ENDPOINT,
];
