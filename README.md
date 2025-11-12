![Packagist version](https://flat.badgen.net/packagist/v/aerni/cloudflared/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/cloudflared)

# Cloudflared for Laravel

An opinionated package to create and manage Cloudflare Tunnels for your Laravel projects.

## Installation

Install the package using Composer:

```bash
composer require aerni/cloudflared
```

## Setup

1. Install [cloudflared](https://developers.cloudflare.com/cloudflare-one/connections/connect-networks/downloads)
2. Run `cloudflared tunnel login` to authenticate the desired domain
3. Install [Laravel Herd](https://herd.laravel.com)

## Basic Usage

This package provides Artisan commands to create, run, and delete Cloudflare tunnels for your project.

### Creating a tunnel

Use the `cloudflared:install` command to create a tunnel, DNS records, and Herd link for your project. After successful creation, the tunnel config is saved to `.cloudflared.yaml` in your project root.

```bash
php artisan cloudflared:install
```

### Running the tunnel

Use the `cloudflared:run` command to start the tunnel.

```bash
php artisan cloudflared:run
```

### Deleting the tunnel

Use the `cloudflared:uninstall` command to delete the tunnel, DNS records, and config.

```bash
php artisan cloudflared:uninstall
```

## Integration with Vite

Use the [vite-plugin-laravel-cloudflared](https://github.com/aerni/vite-plugin-laravel-cloudflared) to provide tunneled access to both your Laravel application and Vite's development server for debugging your frontend.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

Developed by [Michael Aerni](https://michaelaerni.ch)

## Support

For issues and questions, please use the [GitHub Issues](https://github.com/aerni/laravel-cloudflared/issues) page.
