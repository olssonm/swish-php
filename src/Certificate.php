<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Exceptions\CertificateDecodingException;

class Certificate
{
    private ?string $client;

    private ?string $passphrase;

    private bool|string $root;

    private ?string $signing;

    private ?string $signingPassphrase;

    /**
     * Certificate constructor
     *
     * @param string|null $clientPath Path to client certificate
     * @param string|null $passphrase Passphrase for client certificate
     * @param bool|string $rootPath Path to root certificate or true/false
     */
    public function __construct(
        ?string $clientPath,
        ?string $passphrase = null,
        bool|string $rootPath = true,
        ?string $signingPath = null,
        string $signingPassphrase = null
    ) {
        $this->client = $clientPath;
        $this->passphrase = $passphrase;
        $this->root = $rootPath;
        $this->signing = $signingPath;
        $this->signingPassphrase = $signingPassphrase;
    }

    /**
     * @return array<string|null>
     */
    public function getClientCertificate(): array
    {
        return [
            $this->client,
            $this->passphrase,
        ];
    }

    /**
     * @return bool|string
     */
    public function getRootCertificate(): bool|string
    {
        return $this->root;
    }

    /**
     * @return array
     */
    public function getSigningCertificate(): array
    {
        return [
            $this->signing,
            $this->signingPassphrase,
        ];
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        try {
            $content = file_get_contents($this->signing); // @phpstan-ignore argument.type
            $details = openssl_x509_read($content); // @phpstan-ignore argument.type
            $results = openssl_x509_parse($details); // @phpstan-ignore argument.type
            $serial = $results['serialNumberHex']; // @phpstan-ignore offsetAccess.nonOffsetAccessible
        } catch (\Throwable $th) {
            throw new CertificateDecodingException(
                'Could notretrieve the serial number for the certificate. Please check your path and passphrase.',
                0,
                $th
            );
        }

        return $serial;
    }
}
