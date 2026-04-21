# Cloudflared for Laravel

A simple package to create and manage Cloudflare Tunnels for your Laravel projects. Cloudflare Tunnels give you instant public access to your local development environment, similar to Expose or ngrok, but powered by Cloudflare. Perfect for testing webhooks and sharing work-in-progress.

Pair it with [Cloudflared for Vite](https://github.com/aerni/vite-plugin-laravel-cloudflared) to get seamless tunneled access to both your Laravel app and Vite's dev server, making it effortless to debug your frontend on real devices like your iPhone.

## Prerequisites

1. Install [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads)
2. Run `cloudflared tunnel login` to authenticate the desired domain
3. Install [Laravel Herd](https://herd.laravel.com)

## Installation

Install the package using Composer:

```bash
composer require aerni/cloudflared
```

Keep `APP_URL` set to your local Herd URL (e.g. `http://myapp.test`). Do not set it to the public tunnel hostname. That would make cloudflared forward traffic to itself. While the tunnel is running, the package automatically rewrites URL generation to the public Cloudflare URL in web requests, queue workers, and scheduled commands. You never need to edit `.env` whether the tunnel is active or not.

## Basic Usage

### Creating a tunnel

Create a tunnel for your project with a single command. This will create a Cloudflare tunnel, configure DNS records, set up a Herd link, and save the configuration to `.cloudflared.yaml` in your project root.

```bash
php artisan cloudflared:install
```

> **Note:** Run this command again to modify the existing installation. Change the subdomain, create or repair DNS records, or delete and recreate the tunnel.

### Running the tunnel

Start the tunnel to make your local site publicly accessible.

```bash
php artisan cloudflared:run
```

### Deleting the tunnel

Remove the tunnel, DNS records, and configuration when you no longer need it.

```bash
php artisan cloudflared:uninstall
```

## Good to know

### Trusted proxies

Signed URL validation and HTTPS detection through the tunnel rely on Laravel honoring `X-Forwarded-Proto` from Cloudflare. This is the same [TrustProxies setup](https://laravel.com/docs/requests#configuring-trusted-proxies) you'd use behind any TLS-terminating proxy. Most production apps already have this configured.

### Long-running queue workers

The URL rewrite is captured once when the worker boots. Restart long-running workers after starting or stopping the tunnel so new URLs pick up the change.

### Config caching

Make sure your config isn't cached while the tunnel is up. The rewrite runs during bootstrap, so the tunnel URL would get serialized into the cached config and pin there. Clear your config cache if this happens.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

Developed by [Michael Aerni](https://michaelaerni.ch)

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
