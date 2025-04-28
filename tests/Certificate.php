<?php

use Olssonm\Swish\Certificate;
use Olssonm\Swish\Exceptions\CertificateDecodingException;

it('creates a certificate instance with correct properties', function () {
    $certificate = new Certificate(
        clientPath: '/path/to/client.pem',
        passphrase: 'client-passphrase',
        rootPath: '/path/to/root.pem',
        signingPath: '/path/to/signing.pem',
        signingPassphrase: 'signing-passphrase'
    );

    expect($certificate->getClientCertificate())->toBe(['/path/to/client.pem', 'client-passphrase']);
    expect($certificate->getRootCertificate())->toBe('/path/to/root.pem');
    expect($certificate->getSigningCertificate())->toBe(['/path/to/signing.pem', 'signing-passphrase']);
});

it('handles boolean root certificate correctly', function () {
    $certificate = new Certificate(
        clientPath: '/path/to/client.pem',
        passphrase: 'client-passphrase',
        rootPath: true,
        signingPath: '/path/to/signing.pem',
        signingPassphrase: 'signing-passphrase'
    );

    expect($certificate->getRootCertificate())->toBeTrue();
});

it('throws an exception when getSerial fails to read the signing certificate', function () {
    $certificate = new Certificate(
        clientPath: '/path/to/client.pem',
        passphrase: 'client-passphrase',
        rootPath: '/path/to/root.pem',
        signingPath: '/invalid/path/to/signing.pem',
        signingPassphrase: 'signing-passphrase'
    );

    expect(fn () => $certificate->getSerial())
        ->toThrow(CertificateDecodingException::class, 'Could notretrieve the serial number for the certificate. Please check your path and passphrase.');
});
