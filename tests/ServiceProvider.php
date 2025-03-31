<?php
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Olssonm\Swish\Certificate;
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
    $storage = mock(FilesystemManager::class)
        ->shouldReceive('path')
        ->with('relative/path/to/file')
        ->andReturn('/resolved/path/to/file')
        ->getMock();

    $provider = new SwishServiceProvider(mock(Container::class));

    $reflection = new ReflectionClass($provider);
    $method = $reflection->getMethod('resolvePath');
    $method->setAccessible(true);

    expect($method->invoke($provider, $storage, 'relative/path/to/file'))->toBe('/resolved/path/to/file');
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

    // Mock the `path` method for resolving paths
    $storageMock->shouldReceive('path')
        ->with('client-cert.pem')
        ->andReturn('/resolved/client-cert.pem');

    $storageMock->shouldReceive('path')
        ->with('root-cert.pem')
        ->andReturn('/resolved/root-cert.pem');

    $storageMock->shouldReceive('path')
        ->with('signing-cert.pem')
        ->andReturn('/resolved/signing-cert.pem');

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
