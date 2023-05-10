<?php

namespace Olssonm\Swish;

class Certificate
{
    private $root;

    private $key;

    private $passphrase;

    /**
     * Certificate constructor
     *
     * @param string|null $rootPath Path to root certificate
     * @param string|null $keyPath Path to key certificate
     * @param string|null $passphrase Passphrase for key certificate
     */
    public function __construct(?string $rootPath, ?string $keyPath, ?string $passphrase = null)
    {
        $this->root = $rootPath;
        $this->key = $keyPath;
        $this->passphrase = $passphrase;
    }

    public function getRootCertificate(): ?string
    {
        return $this->root;
    }

    public function getKeyCertificate(): ?array
    {
        return [
            $this->key,
            $this->passphrase,
        ];
    }
}
