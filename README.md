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

## Configuration

Publish the config file to customise the package behaviour:

```bash
php artisan vendor:publish --tag=cloudflared-config
```

### Local service URL

By default, `cloudflared:run` writes a tunnel YAML that forwards traffic to `http://{hostname}.test` — the Herd link URL that the command creates automatically via `herd link {hostname}`.

If your local service uses HTTPS or runs on a different port, override the `CLOUDFLARED_SERVICE_URL` environment variable in your `.env` file:

```env
# Use the Herd-secured HTTPS URL (note: herd link myapp.com creates myapp.com.test)
CLOUDFLARED_SERVICE_URL=https://myapp.com.test

# Use a standalone dev server
CLOUDFLARED_SERVICE_URL=http://localhost:8000
```

> **Important:** Never set `CLOUDFLARED_SERVICE_URL` to your public Cloudflare hostname (e.g. `https://myapp.com`). The tunnel daemon resolves this URL locally, so pointing it at the public hostname would route requests back through Cloudflare and create an infinite loop.

### Disable APP_URL override

By default the package overrides `config('app.url')` at runtime when an incoming request arrives through the tunnel. If `APP_URL` is already set to the correct public Cloudflare hostname, you can disable this behaviour entirely:

```env
CLOUDFLARED_OVERRIDE_APP_URL=false
```

This is the recommended setting when `APP_URL` is the public hostname and must remain stable across all contexts (web requests, queue workers, scheduled commands, and Artisan).

#### Why this matters when `APP_URL` is the public URL

If your `APP_URL` is already set to the public Cloudflare hostname (e.g. `https://myapp.com`) — common when generating signed URLs or webhooks that must be publicly reachable — the package previously used `APP_URL` as the tunnel's local service, which caused the loop described above.

The `CLOUDFLARED_SERVICE_URL` option solves this by decoupling the **local origin the tunnel forwards to** from the **public-facing `APP_URL`**. The `setAppUrl()` behaviour that overrides `config('app.url')` for incoming Cloudflare requests is unaffected; it continues to derive the public URL from `APP_URL`'s scheme and the tunnel hostname.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

Developed by [Michael Aerni](https://michaelaerni.ch)

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
