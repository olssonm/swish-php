<?php

namespace Olssonm\Swish\Util;

use ArrayAccess;
use Olssonm\Swish\Exceptions\CertificateDecodingException;

class Crypto
{
    /**
     * Create a sha512 hash of the payload.
     *
     * @param string $payload
     * @return string
     */
    public static function hash(string $payload): string
    {
        $bytes = mb_convert_encoding($payload, 'UTF-8');
        $hash = hash('sha512', $bytes, true);
        return $hash;
    }

    /**
     * Sign a hash with a certificate.
     *
     * @param string $hash
     * @param string $certificate
     * @return string
     * @throws CertificateDecodingException
     */
    public static function sign(string $hash, string $certificate): string
    {
        $signature = null;

        // Load your private key
        $key = file_get_contents($certificate);

        if (!$key) {
            throw new CertificateDecodingException('Failed to load certificate');
        }

        $id = openssl_get_privatekey($key);

        // Sign the hash
        openssl_sign($hash, $signature, $id, OPENSSL_ALGO_SHA512); // @phpstan-ignore argument.type

        return $signature;
    }

    /**
     * Hash and sign a payload.
     *
     * @param ArrayAccess $payload
     * @param ?string $certificate
     * @return string
     */
    public static function hashAndSign(ArrayAccess $payload, ?string $certificate): string
    {
        $data = json_encode($payload);

        if (!$data || !$certificate) {
            throw new CertificateDecodingException('Failed to encode payload');
        }

        $hash = self::hash($data);
        $signature = self::sign($hash, $certificate);

        return base64_encode($signature);
    }
}
