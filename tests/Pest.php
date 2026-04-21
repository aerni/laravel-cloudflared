<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

const LOCAL_URL = 'http://myapp.test';
const PUBLIC_URL = 'https://myapp.example.com';

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        Http::preventStrayRequests();

        $this->tempHome = sys_get_temp_dir().'/cloudflared-test-'.uniqid();
        mkdir("{$this->tempHome}/.cloudflared", recursive: true);
        putenv("HOME={$this->tempHome}");
    })
    ->afterEach(function () {
        File::deleteDirectory($this->tempHome);
        File::delete(base_path('.cloudflared.yaml'));
    })
    ->in(__DIR__);

function installCloudflared(): void
{
    $hostname = parse_url(PUBLIC_URL, PHP_URL_HOST);

    File::put(base_path('.cloudflared.yaml'), <<<YAML
id: fake-tunnel-id
name: fake-tunnel-name
hostname: {$hostname}
vite: false
YAML);
}

function runCloudflared(): void
{
    File::put(getenv('HOME').'/.cloudflared/fake-tunnel-id.yaml', 'tunnel: fake-tunnel-id');
}

function assertUsesLocalUrl(): void
{
    expect(config('app.url'))->toBe(LOCAL_URL)
        ->and(URL::to('/'))->toStartWith('http://');
}

function assertUsesPublicUrl(): void
{
    expect(config('app.url'))->toBe(PUBLIC_URL)
        ->and(URL::to('/'))->toBe(PUBLIC_URL)
        ->and(URL::asset('logo.png'))->toStartWith(PUBLIC_URL);
}
