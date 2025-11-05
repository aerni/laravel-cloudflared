<?php

namespace Aerni\Cloudflared;

use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredRun;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;
use Aerni\Cloudflared\Facades\Cloudflared;
use Illuminate\Support\ServiceProvider;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! Cloudflared::isInstalled()) {
            return;
        }

        if (request()->host() !== Cloudflared::tunnelConfig()->hostname()) {
            return;
        }

        config()->set('app.url', Cloudflared::tunnelConfig()->url());
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'cloudflared');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CloudflaredInstall::class,
                CloudflaredRun::class,
                CloudflaredUninstall::class,
            ]);
        }
    }
}
