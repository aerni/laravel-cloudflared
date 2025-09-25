<?php

namespace Aerni\Cloudflared;

use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredStart;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;
use Illuminate\Support\ServiceProvider;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        if (! $this->isCloudflaredRequest()) {
            return;
        }

        config()->set('app.url', env('CLOUDFLARED_APP_URL'));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CloudflaredInstall::class,
                CloudflaredStart::class,
                CloudflaredUninstall::class,
            ]);
        }
    }

    protected function isCloudflaredRequest(): bool
    {
        return request()->host() === parse_url(env('CLOUDFLARED_APP_URL'), PHP_URL_HOST);
    }
}
