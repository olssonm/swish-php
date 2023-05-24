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
     * @param string|null $keyPath Path to key certificate
     * @param string|null $passphrase Passphrase for key certificate
     * @param bool|string $rootPath Path to root certificate or true/false
     */
    public function __construct(?string $keyPath, ?string $passphrase = null, bool|string $rootPath = true)
    {
        $this->root = $rootPath;
        $this->key = $keyPath;
        $this->passphrase = $passphrase;
    }

    public function getKeyCertificate(): ?array
    {
        return [
            $this->key,
            $this->passphrase,
        ];
    }

    public function getRootCertificate(): ?string
    {
        return $this->root;
    }
}
