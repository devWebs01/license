# Laravel Licensing Client

**Package:** `devwebs01/laravel-licensing-client`  
**Version:** 1.0.0  
**Architecture:** GitHub Raw JSON Sync — no API server required.

## Installation

```bash
composer require devwebs01/laravel-licensing-client
```

## Configuration

```env
LICENSING_GITHUB_RAW=https://raw.githubusercontent.com/owner/repo/main
LICENSING_KEY=XXXX-XXXX-XXXX-XXXX
LICENSING_GRACE_DAYS=7
LICENSING_ADMIN_CONTACT=admin@company.com
```

## Commands

```bash
php artisan license:activate XXXX-XXXX-XXXX-XXXX
php artisan license:status
php artisan license:sync
php artisan license:check
```

## Quick Start

1. Set `LICENSING_GITHUB_RAW` to your GitHub raw content base URL
2. Set `LICENSING_KEY` to the license key
3. Run `php artisan license:activate {key}`
4. Add `->middleware('license')` to protected routes

## How It Works

- License Monitor pushes JSON status files to GitHub
- Client fetches from `https://raw.githubusercontent.com/.../licenses/{hash}.json`
- Status is cached locally with offline grace period
- No API server, no HMAC, no device fleet management

## Testing

```bash
vendor/bin/phpunit
```

## Documentation

See `docs/` in the project root for full architecture and integration guide.
