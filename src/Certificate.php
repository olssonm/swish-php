<?php

namespace Olssonm\Swish;

use Olssonm\Swish\Exceptions\CertificateDecodingException;

class Certificate
{
    private ?string $client;

    private ?string $passphrase;

    private bool|string $root;

    private ?string $signing;

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
        ?string $signingPath = null
    )
    {
        $this->client = $clientPath;
        $this->passphrase = $passphrase;
        $this->root = $rootPath;
        $this->signing = $signingPath;
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
     * @return bool|string
     */
    public function getSigningCertificate(): null|string
    {
        return $this->signing;
    }

    /**
     * @return string
     */
    public function getSerial(): string
    {
        try {
            $content = file_get_contents($this->client);
            $details = openssl_x509_read($content);
            $results = openssl_x509_parse($details)['serialNumberHex'];
        } catch (\Throwable $th) {
            throw new CertificateDecodingException('Could not parse and retrieve the serial number for the certificate. Please check your path and passphrase.', 0, $th);
        }

        return $results;
    }
}
