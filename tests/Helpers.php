<?php

use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

function get_real_client($certificate = null)
{
    if (!$certificate) {
        $certificate = new Certificate(
            __DIR__ . '/certificates/Swish_Merchant_TestCertificate_1234679304.pem',
            'swish',
            __DIR__ . '/certificates/Swish_TLS_RootCA.pem',
        );
    }
    return new Client($certificate, Client::TEST_ENDPOINT);
}
