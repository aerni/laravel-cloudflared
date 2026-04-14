<?php

use Aerni\Cloudflared\CloudflaredServiceProvider;

// Expose the protected setAppUrl() method for testing without modifying the class.
class TestableCloudflaredServiceProvider extends CloudflaredServiceProvider
{
    public function triggerSetAppUrl(): void
    {
        $this->setAppUrl();
    }
}

describe('setAppUrl()', function () {
    it('does not modify APP_URL when override_app_url is false', function () {
        config([
            'app.url' => 'https://myapp.com',
            'cloudflared.override_app_url' => false,
        ]);

        (new TestableCloudflaredServiceProvider(app()))->triggerSetAppUrl();

        expect(config('app.url'))->toBe('https://myapp.com');
    });

    it('does not modify APP_URL when cloudflared is not installed', function () {
        config([
            'app.url' => 'https://myapp.com',
            'cloudflared.override_app_url' => true,
        ]);

        // No .cloudflared.yaml exists in the test environment, so isInstalled()
        // returns false and setAppUrl() exits early.
        (new TestableCloudflaredServiceProvider(app()))->triggerSetAppUrl();

        expect(config('app.url'))->toBe('https://myapp.com');
    });
});

describe('config defaults', function () {
    it('defaults service_url to null', function () {
        // Simulate mergeConfigFrom by loading the config file directly.
        $defaults = require __DIR__.'/../config/cloudflared.php';

        expect($defaults['service_url'])->toBeNull();
    });

    it('defaults override_app_url to true', function () {
        $defaults = require __DIR__.'/../config/cloudflared.php';

        // Default is true (env fallback); in tests CLOUDFLARED_OVERRIDE_APP_URL is unset.
        expect($defaults['override_app_url'])->toBeTrue();
    });
});
