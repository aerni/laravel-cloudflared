<?php

use Aerni\Cloudflared\Data\ProjectConfig;
use Aerni\Cloudflared\Data\TunnelConfig;

function makeTunnelConfig(string $hostname = 'myapp.com'): TunnelConfig
{
    return new TunnelConfig(new ProjectConfig(
        id: 'abc-123',
        name: 'test-tunnel',
        hostname: $hostname,
    ));
}

describe('service()', function () {
    it('returns the configured service URL when CLOUDFLARED_SERVICE_URL is set', function () {
        config(['cloudflared.service_url' => 'https://myapp.com.test']);

        expect(makeTunnelConfig()->service())->toBe('https://myapp.com.test');
    });

    it('falls back to http://{hostname}.test when service URL is null', function () {
        config(['cloudflared.service_url' => null]);

        expect(makeTunnelConfig('myapp.com')->service())->toBe('http://myapp.com.test');
    });

    it('falls back to http://{hostname}.test when service URL is empty string', function () {
        config(['cloudflared.service_url' => '']);

        expect(makeTunnelConfig('myapp.com')->service())->toBe('http://myapp.com.test');
    });

    it('builds the fallback using the full tunnel hostname', function () {
        config(['cloudflared.service_url' => null]);

        expect(makeTunnelConfig('sub.example.com')->service())->toBe('http://sub.example.com.test');
    });

    it('accepts a standalone dev server URL', function () {
        config(['cloudflared.service_url' => 'http://localhost:8000']);

        expect(makeTunnelConfig()->service())->toBe('http://localhost:8000');
    });
});

describe('url()', function () {
    it('derives the scheme from APP_URL, not from service()', function () {
        config([
            'app.url' => 'https://myapp.com',
            'cloudflared.service_url' => 'http://myapp.com.test',
        ]);

        expect(makeTunnelConfig('myapp.com')->url())->toBe('https://myapp.com');
    });

    it('preserves HTTPS from APP_URL even when the local service is HTTP', function () {
        config([
            'app.url' => 'https://myapp.com',
            'cloudflared.service_url' => null,
        ]);

        // service() returns http://myapp.com.test — url() must still be https
        expect(makeTunnelConfig('myapp.com')->url())->toBe('https://myapp.com');
    });

    it('uses HTTP when APP_URL is HTTP', function () {
        config([
            'app.url' => 'http://myapp.com',
            'cloudflared.service_url' => null,
        ]);

        expect(makeTunnelConfig('myapp.com')->url())->toBe('http://myapp.com');
    });

    it('combines APP_URL scheme with the tunnel hostname, not with the APP_URL host', function () {
        config([
            'app.url' => 'https://myapp.test',
            'cloudflared.service_url' => null,
        ]);

        // APP_URL host is myapp.test but the public tunnel hostname is different
        expect(makeTunnelConfig('myapp.com')->url())->toBe('https://myapp.com');
    });
});
