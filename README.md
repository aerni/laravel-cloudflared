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

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

Developed by [Michael Aerni](https://michaelaerni.ch)

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
