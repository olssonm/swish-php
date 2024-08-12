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
            $certificate = new Certificate(
                clientPath: $app['config']['swish.certificates.client'],
                passphrase: $app['config']['swish.certificates.password'],
                rootPath: $app['config']['swish.certificates.root'],
                signingPath: $app['config']['swish.certificates.signing'],
                signingPassphrase: $app['config']['swish.certificates.signing_password'],
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
