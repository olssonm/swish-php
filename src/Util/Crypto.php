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
     * @param ?string $passphrase
     * @return string
     * @throws CertificateDecodingException
     */
    public static function sign(string $hash, string $certificate, ?string $passphrase): string
    {
        $signature = null;

        // Load your private key
        try {
            $key = file_get_contents($certificate);
        } catch (\Throwable $th) {
            throw new CertificateDecodingException('Failed to load certificate');
        }


        if (!$key) {
            throw new CertificateDecodingException('Failed to load certificate');
        }

        $id = openssl_get_privatekey($key, $passphrase);

        // Sign the hash
        openssl_sign($hash, $signature, $id, OPENSSL_ALGO_SHA512); // @phpstan-ignore argument.type

        return $signature;
    }

    /**
     * Hash and sign a payload.
     *
     * @param ArrayAccess $payload
     * @param array<string|null> $certificate
     * @return string
     */
    public static function hashAndSign(ArrayAccess $payload, array $certificate): string
    {
        $data = json_encode($payload);

        if (!$data || !$certificate) {
            throw new CertificateDecodingException('Failed to encode payload');
        }

        $hash = self::hash($data);
        $signature = self::sign($hash, $certificate[0] ?? '', $certificate[1]);

        return base64_encode($signature);
    }
}
