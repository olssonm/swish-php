<?php

namespace Olssonm\Swish\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

class SwishServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $source = realpath($raw = __DIR__ . '/../../config/swish.php') ?: $raw;

        $this->publishes([$source => config_path('swish.php')]);

        $this->mergeConfigFrom($source, 'swish');

        $this->app->singleton('swish', function (Container $app): Client {
            /** @var \Illuminate\Config\Repository $config */
            $config = $app->get('config');

            /** @var \Illuminate\Filesystem\FilesystemAdapter $storage */
            $storage = $app->get('filesystem');

            $certificate = new Certificate(
                clientPath: $storage->path($config['swish.certificates.client']),
                passphrase: $config['swish.certificates.password'],
                rootPath: $storage->path($config['swish.certificates.root']),
                signingPath: $storage->path($config['swish.certificates.signing']),
                signingPassphrase: $config['swish.certificates.signing_password']
            );

            return new Client($certificate, $config['swish.endpoint']);
        });

        $this->app->alias('swish', Client::class);
    }

    /**
     * @return array<string>
     */
    public function provides(): array
    {
        return ['swish'];
    }
}
