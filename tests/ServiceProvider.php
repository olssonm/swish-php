<?php
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Olssonm\Swish\Client;
use Olssonm\Swish\Providers\SwishServiceProvider;

it('resolves absolute paths correctly', function () {
    $storage = mock(FilesystemManager::class);
    $provider = new SwishServiceProvider(mock(Container::class));

    $absolutePath = '/absolute/path/to/file';

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('resolvePath');
    $method->setAccessible(true);

    expect($method->invoke($provider, $storage, $absolutePath))->toBe($absolutePath);
});

it('resolves relative paths correctly', function () {
    $disk = mock(\Illuminate\Contracts\Filesystem\Filesystem::class)
        ->shouldReceive('path')
        ->with('relative/path/to/file')
        ->andReturn('/resolved/path/to/file')
        ->getMock();

    $storage = mock(FilesystemManager::class)
        ->shouldReceive('disk')
        ->with('local')
        ->andReturn($disk)
        ->getMock();

    $provider = new SwishServiceProvider(mock(Container::class));

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('resolvePath');
    $method->setAccessible(true);

    expect($method->invoke($provider, $storage, 'relative/path/to/file'))->toBe('/resolved/path/to/file');
    expect($method->invoke($provider, $storage, ''))->toBe('');
    expect($method->invoke($provider, $storage, null))->toBe('');
});

it('checks absolute paths correctly', function () {
    $provider = new SwishServiceProvider(mock(Container::class));

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('isAbsolutePath');
    $method->setAccessible(true);

    expect($method->invoke($provider, '/absolute/path'))->toBeTrue();
    expect($method->invoke($provider, 'C:\\absolute\\path'))->toBeTrue();
    expect($method->invoke($provider, 'relative/path'))->toBeFalse();
    expect($method->invoke($provider, ''))->toBeFalse();
});

it('registers the swish singleton correctly', function () {
    $container = mock(Container::class);
    $configMock = mock(\Illuminate\Config\Repository::class)->makePartial();
    $storageMock = mock(FilesystemManager::class);

    // Mock the `get` method for retrieving dependencies
    $container->shouldReceive('get')
        ->with('config')
        ->andReturn($configMock);

    $container->shouldReceive('get')
        ->with('filesystem')
        ->andReturn($storageMock);

    // Mock the `make` method for resolving dependencies
    $container->shouldReceive('make')
        ->with('config')
        ->andReturn($configMock);

    $container->shouldReceive('make')
        ->with('filesystem')
        ->andReturn($storageMock);

    // Mock configuration values
    $configMock->shouldReceive('get')
        ->with('swish.certificates.client')
        ->andReturn('client-cert.pem');

    $configMock->shouldReceive('get')
        ->with('swish.certificates.password')
        ->andReturn('password');

    $configMock->shouldReceive('get')
        ->with('swish.certificates.root')
        ->andReturn('root-cert.pem');

    $configMock->shouldReceive('get')
        ->with('swish.certificates.signing')
        ->andReturn('signing-cert.pem');

    $configMock->shouldReceive('get')
        ->with('swish.certificates.signing_password')
        ->andReturn('signing-password');

    $configMock->shouldReceive('get')
        ->with('swish.endpoint')
        ->andReturn('https://swish.example.com');

    $configMock->shouldReceive('get')
        ->with('swish.disk', 'local')
        ->andReturn('local');

    // Mock the `path` method on the configured disk for resolving paths
    $diskMock = mock(\Illuminate\Contracts\Filesystem\Filesystem::class);

    $diskMock->shouldReceive('path')
        ->with('client-cert.pem')
        ->andReturn('/resolved/client-cert.pem');

    $diskMock->shouldReceive('path')
        ->with('root-cert.pem')
        ->andReturn('/resolved/root-cert.pem');

    $diskMock->shouldReceive('path')
        ->with('signing-cert.pem')
        ->andReturn('/resolved/signing-cert.pem');

    $storageMock->shouldReceive('disk')
        ->with('local')
        ->andReturn($diskMock);

    // Mock the `singleton` method to verify the closure
    $container->shouldReceive('singleton')
        ->with('swish', \Mockery::on(function ($closure) use ($container) {
            $client = $closure($container);
            expect($client)->toBeInstanceOf(Client::class);
            return true;
        }))
        ->once();

    // Mock the `alias` method
    $container->shouldReceive('alias')
        ->with('swish', Client::class)
        ->once();

    // Create the provider instance
    $provider = new SwishServiceProvider($container);

    // Call the `register` method
    $provider->register();
});

it('provides the correct services', function () {
    $provider = new SwishServiceProvider(mock(Container::class));
    expect($provider->provides())->toBe(['swish']);
});

it('resolves facade with missing keys', function () {
    // Expect the Swish facade to fail if the config keys are null
    config()->set('swish.certificates.client', null);
    config()->set('swish.certificates.password', null);
    config()->set('swish.certificates.root', null);
    config()->set('swish.certificates.signing', null);
    config()->set('swish.certificates.signing_password', null);

    // In <= v3.2 this threw an exception
    $client = get_facade_client();
    expect($client)->toBeInstanceOf(Client::class);
});

it('resolves facade with true key', function () {
    // Expect the Swish facade to fail if the config keys are null
    config()->set('swish.certificates.client', '/path/to/client.pem');
    config()->set('swish.certificates.password', 'swish');
    config()->set('swish.certificates.root', true);
    config()->set('swish.certificates.signing', '/path/to/signing.pem');
    config()->set('swish.certificates.signing_password', 'swish');

    // Test that $clients Certificate root is set to true
    $client = get_facade_client();
    expect($client)->toBeInstanceOf(Client::class);
    expect($client->getCertificate()->getRootCertificate())->toBeTrue();
});

it('resolves a relative certificate against the local disk, not the default disk', function () {
    // The default disk is something other than the local disk (e.g. S3/R2 on Laravel Cloud,
    // whose path() returns the bare object key instead of an absolute filesystem path).
    $localRoot = sys_get_temp_dir() . '/swish-local-root';
    $defaultRoot = sys_get_temp_dir() . '/swish-default-root';

    config()->set('filesystems.disks.local', ['driver' => 'local', 'root' => $localRoot]);
    config()->set('filesystems.disks.objectstore', ['driver' => 'local', 'root' => $defaultRoot]);
    config()->set('filesystems.default', 'objectstore');

    config()->set('swish.certificates.client', 'swish/client.pem');

    [$clientPath] = get_facade_client()->getCertificate()->getClientCertificate();

    expect($clientPath)->toBe($localRoot . '/swish/client.pem');
});

it('resolves a relative certificate against a custom configured disk', function () {
    $certsRoot = sys_get_temp_dir() . '/swish-certs-root';

    config()->set('filesystems.disks.certs', ['driver' => 'local', 'root' => $certsRoot]);
    config()->set('swish.disk', 'certs');
    config()->set('swish.certificates.client', 'swish/client.pem');

    [$clientPath] = get_facade_client()->getCertificate()->getClientCertificate();

    expect($clientPath)->toBe($certsRoot . '/swish/client.pem');
});

it('copies the client certificate from the copy disk when missing on the destination disk', function () {
    $destRoot = sys_get_temp_dir() . '/swish-dest-root';
    $copyRoot = sys_get_temp_dir() . '/swish-copy-root';
    @mkdir($copyRoot, 0777, true);
    file_put_contents($copyRoot . '/client.pem', 'CERT-BYTES');
    @unlink($destRoot . '/client.pem');

    config()->set('filesystems.disks.dest', ['driver' => 'local', 'root' => $destRoot]);
    config()->set('filesystems.disks.copy', ['driver' => 'local', 'root' => $copyRoot]);
    config()->set('swish.disk', 'dest');
    config()->set('swish.copy_disk', 'copy');
    config()->set('swish.certificates.client', 'client.pem');

    get_facade_client();

    expect(file_exists($destRoot . '/client.pem'))->toBeTrue();
    expect(file_get_contents($destRoot . '/client.pem'))->toBe('CERT-BYTES');
});

it('does not copy the certificate when no copy disk is configured', function () {
    $destRoot = sys_get_temp_dir() . '/swish-dest-root-2';
    $copyRoot = sys_get_temp_dir() . '/swish-copy-root-2';
    @mkdir($copyRoot, 0777, true);
    file_put_contents($copyRoot . '/client.pem', 'CERT-BYTES');
    @unlink($destRoot . '/client.pem');

    config()->set('filesystems.disks.dest', ['driver' => 'local', 'root' => $destRoot]);
    config()->set('filesystems.disks.copy', ['driver' => 'local', 'root' => $copyRoot]);
    config()->set('swish.disk', 'dest');
    config()->set('swish.copy_disk', null);
    config()->set('swish.certificates.client', 'client.pem');

    get_facade_client();

    expect(file_exists($destRoot . '/client.pem'))->toBeFalse();
});
