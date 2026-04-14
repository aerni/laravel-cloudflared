<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local Service URL
    |--------------------------------------------------------------------------
    |
    | The local URL the cloudflared daemon forwards incoming tunnel traffic to.
    | This must be a URL reachable from your local machine — the origin server
    | behind your tunnel (e.g. your Herd site or a local dev server).
    |
    | When set to null, the package defaults to http://{hostname}.test, which
    | matches the Herd link URL that cloudflared:run creates automatically via
    | `herd link {hostname}`.
    |
    | Override this when your local service uses HTTPS or a different port:
    |
    |   CLOUDFLARED_SERVICE_URL=https://myapp.test
    |   CLOUDFLARED_SERVICE_URL=http://localhost:8000
    |
    | Do NOT set this to your public Cloudflare hostname (e.g. myapp.com). That
    | would cause the tunnel daemon to forward requests back through Cloudflare,
    | creating an infinite loop.
    |
    */

    'service_url' => env('CLOUDFLARED_SERVICE_URL'),

];
