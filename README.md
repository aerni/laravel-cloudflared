![Packagist version](https://flat.badgen.net/packagist/v/aerni/cloudflared/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/cloudflared) ![License](https://flat.badgen.net/github/license/aerni/laravel-cloudflared)

# Cloudflared for Laravel

This package is the Laravel companion for the [vite-plugin-cloudflared](https://github.com/aerni/vite-plugin-cloudflared). It provides Artisan commands to easily manage Cloudflare Tunnels for your Laravel development environment.

## Features

- **Easy Tunnel Management**: Create, run, and delete Cloudflare Tunnels with simple Artisan commands
- **Automatic DNS Configuration**: Automatically creates DNS records for your application and Vite dev server
- **Laravel Herd Integration**: Seamlessly integrates with Laravel Herd for local development

## Requirements

- `cloudflared` CLI tool installed on your local machine
- PHP 8.2 or higher
- Laravel 11.x or 12.x
- Laravel Herd

## Installation

Install the package using Composer:

```bash
composer require aerni/cloudflared
```

## Setup

Before using this package, make sure you have:

1. **Cloudflared CLI installed**: Follow the [Cloudflare documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads/) to install `cloudflared`
2. **Cloudflared authenticated**: Run `cloudflared tunnel login` to authenticate with your Cloudflare account

## Commands

### `php artisan cloudflared:install`

Creates a new Cloudflare Tunnel for your project.

**What it does:**
- Prompts for a hostname (e.g., `myapp.yourdomain.com`)
- Creates a new Cloudflare Tunnel
- Generates a `.cloudflared.yaml` configuration file
- Creates DNS records for both your app and Vite dev server (`vite-{hostname}`)
- Creates a Laravel Herd link for local development

**Usage:**
```bash
php artisan cloudflared:install
```

The command will interactively prompt you for:
- **Hostname**: The domain you want to use (must include a subdomain)
- **Confirmation**: Whether to overwrite existing DNS records

### `php artisan cloudflared:run`

Runs the Cloudflare Tunnel for your project.

**What it does:**
- Generates the tunnel configuration
- Starts the `cloudflared` process
- Handles graceful shutdown on interruption signals (Ctrl+C, SIGTERM, etc.)

**Usage:**
```bash
php artisan cloudflared:run
```

**Note:** Keep this command running while you want the tunnel active. Use Ctrl+C to stop.

### `php artisan cloudflared:uninstall`

Removes the Cloudflare Tunnel and cleans up associated resources.

**What it does:**
- Deletes the Cloudflare Tunnel
- Removes the `.cloudflared.yaml` configuration file
- Removes tunnel configuration from `~/.cloudflared/`
- Unlinks the Laravel Herd site

**Usage:**
```bash
php artisan cloudflared:uninstall
```

## Configuration

### Environment Variables

Add the following to your `.env` file:

```env
CLOUDFLARED_APP_URL=https://your-tunnel-hostname.yourdomain.com
```

This ensures your Laravel application uses the correct URL when accessed through the tunnel.

### Project Configuration

The package creates a `.cloudflared.yaml` file in your project root with:

```yaml
tunnel: your-tunnel-id
hostname: your-tunnel-hostname.yourdomain.com
```

## How It Works

1. **Installation**: Creates a tunnel, DNS records, and local configuration
2. **Running**: Generates a tunnel config file and starts the `cloudflared` process
3. **Request Handling**: The service provider automatically sets the correct `app.url` for tunnel requests
4. **Cleanup**: Uninstall removes all traces of the tunnel and configuration

## Integration with Vite Plugin

This package works seamlessly with [vite-plugin-cloudflared](https://github.com/aerni/vite-plugin-cloudflared) to provide tunneled access to both your Laravel application and Vite's development server.

When you run `cloudflared:install`, it automatically creates DNS records for:
- Your main application: `hostname.yourdomain.com`
- Your Vite dev server: `vite-hostname.yourdomain.com`

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- **Michael Aerni** - [https://www.michaelaerni.ch](https://www.michaelaerni.ch)
- All contributors

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
