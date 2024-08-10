<?php

namespace Olssonm\Swish\Test;

use Olssonm\Swish\Providers\SwishServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            SwishServiceProvider::class
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
