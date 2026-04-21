<?php

use Aerni\Cloudflared\CloudflaredServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

function bootProvider(): void
{
    $provider = new CloudflaredServiceProvider(app());
    $method = new ReflectionMethod($provider, 'setAppUrl');
    $method->setAccessible(true);
    $method->invoke($provider);
}

function bootProviderForWebRequest(string $url): void
{
    $property = new ReflectionProperty(app(), 'isRunningInConsole');
    $property->setAccessible(true);
    $property->setValue(app(), false);

    app()->instance('request', Request::create($url, 'GET'));

    bootProvider();
}

describe('setAppUrl from a console context', function () {
    it('uses the local url when the tunnel is not installed', function () {
        bootProvider();
        assertUsesLocalUrl();
    });

    it('uses the local url when the tunnel is not running', function () {
        installCloudflared();
        bootProvider();
        assertUsesLocalUrl();
    });

    it('uses the public url when the tunnel is running', function () {
        installCloudflared();
        runCloudflared();
        bootProvider();
        assertUsesPublicUrl();
    });

    it('generates signed urls using the public url when the tunnel is running', function () {
        installCloudflared();
        runCloudflared();
        Route::get('/webhook', fn () => '')->name('webhook');
        bootProvider();
        expect(URL::signedRoute('webhook'))->toStartWith(PUBLIC_URL);
    });
});

describe('setAppUrl from a web context', function () {
    it('uses the local url when the tunnel is not installed', function () {
        bootProviderForWebRequest(LOCAL_URL);
        assertUsesLocalUrl();
    });

    it('uses the local url when the request comes from the local url', function () {
        installCloudflared();
        runCloudflared();
        bootProviderForWebRequest(LOCAL_URL);
        assertUsesLocalUrl();
    });

    it('uses the public url when the request comes from the public url', function () {
        installCloudflared();
        runCloudflared();
        bootProviderForWebRequest(PUBLIC_URL);
        assertUsesPublicUrl();
    });

    it('uses the public url when the request comes from the public url even if the tunnel is not running', function () {
        installCloudflared();
        bootProviderForWebRequest(PUBLIC_URL);
        assertUsesPublicUrl();
    });

    it('generates signed urls using the public url when the request comes from the public url', function () {
        installCloudflared();
        runCloudflared();
        Route::get('/webhook', fn () => '')->name('webhook');
        bootProviderForWebRequest(PUBLIC_URL);
        expect(URL::signedRoute('webhook'))->toStartWith(PUBLIC_URL);
    });
});
