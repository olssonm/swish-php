<?php

namespace Olssonm\Swish\Util;

class Hash
{
    public static function make(string $payload)
    {
        $bytes = mb_convert_encoding($payload, 'UTF-8');
        $hash = hash('sha512', $bytes, true);
        return $hash;
    }

    public static function sign(string $hash, string $certificate)
    {
        $signature = null;

        // Load your private key
        $key = file_get_contents($certificate);
        $id = openssl_get_privatekey($key);

        // Sign the hash
        openssl_sign($hash, $signature, $id, OPENSSL_ALGO_SHA512);

        return $signature;
    }
}
