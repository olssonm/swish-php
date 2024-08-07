<?php

namespace Olssonm\Swish\Providers;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Olssonm\Swish\Certificate;
use Olssonm\Swish\Client;

class SwishServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $source = realpath($raw = __DIR__ . '/../../config/swish.php') ?: $raw;

        $this->publishes([$source => config_path('swish.php')]);

        $this->mergeConfigFrom($source, 'swish');
    }

    public function register(): void
    {
        $this->app->singleton('swish', function (Container $app): Client {
            $certificate = new Certificate(
                clientPath: $app['config']['swish.certificates.client'],
                passphrase: $app['config']['swish.certificates.password'],
                rootPath: $app['config']['swish.certificates.root']
            );

            return new Client($certificate, $app['config']['swish.endpoint']);
        });

        $this->app->alias('swish', Client::class);
    }

    /** @codeCoverageIgnore */
    public function provides(): array
    {
        return ['swish'];
    }
}
