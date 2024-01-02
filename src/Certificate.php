<?php

namespace Olssonm\Swish;

class Certificate
{
    private ?string $client;

    private ?string $passphrase;

    private bool|string $root;

    /**
     * Certificate constructor
     *
     * @param string|null $clientPath Path to client certificate
     * @param string|null $passphrase Passphrase for client certificate
     * @param bool|string $rootPath Path to root certificate or true/false
     */
    public function __construct(?string $clientPath, ?string $passphrase = null, bool|string $rootPath = true)
    {
        $this->client = $clientPath;
        $this->passphrase = $passphrase;
        $this->root = $rootPath;
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

    public function getRootCertificate(): bool|string
    {
        return $this->root;
    }
}
