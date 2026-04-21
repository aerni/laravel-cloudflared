<?php

namespace Tests;

use Aerni\Cloudflared\CloudflaredServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CloudflaredServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.url', LOCAL_URL);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
