<?php

namespace Aerni\Cloudflared;

use Aerni\Cloudflared\Clients\CloudflareClient;
use Aerni\Cloudflared\Console\Commands\CloudflaredInstall;
use Aerni\Cloudflared\Console\Commands\CloudflaredRun;
use Aerni\Cloudflared\Console\Commands\CloudflaredUninstall;
use Aerni\Cloudflared\Data\Certificate;
use Aerni\Cloudflared\Facades\Cloudflared;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class CloudflaredServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerCloudflareClient();
        $this->setAppUrl();
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

    protected function registerCloudflareClient(): void
    {
        $this->app->singleton(CloudflareClient::class, fn () => new CloudflareClient(Certificate::load()));
    }

    protected function setAppUrl(): void
    {
        if (! Cloudflared::isInstalled()) {
            return;
        }

        $tunnel = Cloudflared::tunnelConfig();

        /**
         * Console: use the public URL when the tunnel daemon is running (its YAML exists).
         * Web: use the public URL only when the request arrived through the tunnel.
         */
        $usePublicUrl = $this->app->runningInConsole()
            ? File::exists($tunnel->path())
            : request()->host() === $tunnel->hostname();

        if (! $usePublicUrl) {
            return;
        }

        URL::useOrigin($tunnel->url());
        URL::forceScheme('https');
        Config::set('app.url', $tunnel->url());
    }
}
