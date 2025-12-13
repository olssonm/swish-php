<?php

namespace Olssonm\Swish\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\ServiceProvider;
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

class SwishServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $source = realpath($raw = __DIR__ . '/../../config/swish.php') ?: $raw;

        $this->publishes([$source => config_path('swish.php')], 'config');

        $this->mergeConfigFrom($source, 'swish');

        $this->app->singleton('swish', function (Container $app): Client {
            /** @var \Illuminate\Config\Repository $config */
            $config = $app->get('config');

            /** @var \Illuminate\Filesystem\FilesystemManager $storage */
            $storage = $app->get('filesystem');

            $certificate = new Certificate(
                clientPath: $this->resolvePath($storage, $config->get('swish.certificates.client')),
                passphrase: $config->get('swish.certificates.password'),
                rootPath: is_bool($config->get('swish.certificates.root'))
                    ? $config->get('swish.certificates.root')
                    : $this->resolvePath($storage, $config->get('swish.certificates.root')),
                signingPath: $this->resolvePath($storage, $config->get('swish.certificates.signing')),
                signingPassphrase: $config->get('swish.certificates.signing_password')
            );

            return new Client($certificate, $config->get('swish.endpoint'));
        });

        $this->app->alias('swish', Client::class);
    }

    private function resolvePath(FilesystemManager $storage, ?string $path): string
    {
        if (empty($path)) {
            return '';
        }

        return $this->isAbsolutePath($path) ? $path : $storage->path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if ($path[0] === '/' || $path[0] === '\\') {
            return true;
        }

        return strlen($path) >= 3
            && ctype_alpha($path[0])
            && $path[1] === ':'
            && ($path[2] === '\\' || $path[2] === '/');
    }

    /** @return array<string> */
    public function provides(): array
    {
        return ['swish'];
    }
}
