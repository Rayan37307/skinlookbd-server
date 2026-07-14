# SkinLookBD API

Backend REST API for SkinLookBD, a single-vendor skincare e-commerce store targeting Bangladesh.
Built with Laravel 13, targeting Namecheap shared hosting (Apache/LiteSpeed + PHP-FPM, MySQL,
no persistent app server, no Redis).

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for how the codebase is organized, the data
model, auth/roles, and the reasoning behind the less-obvious decisions.

## Requirements

- PHP 8.4, Composer
- MySQL/MariaDB in production; SQLite is used automatically for local dev and tests

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

`migrate --seed` creates the catalog (categories, skin types, sample products), shipping zones,
and two accounts for local testing:

| Email | Phone | Role |
|---|---|---|
| `test@example.com` | `01700000000` | `customer` |
| `admin@example.com` | `01700000001` | `super-admin` |

Both use the default factory password (`password`). Serve locally with `php artisan serve` (or
Laravel Herd, which this project was developed under) — the API is versioned under `/api/v1`.

## Testing

```bash
php artisan test
vendor/bin/pint          # code style; add --dirty if working in a git repo
```

Feature tests run against an in-memory SQLite database (see `tests/Pest.php`).

## API documentation

Generated via [Scribe](https://scribe.knuckles.wtf/laravel/) from routes and FormRequest
validation rules:

- `public/docs/index.html` — browsable HTML reference
- `public/docs/openapi.yaml` — OpenAPI 3.0.3 spec
- `public/docs/collection.json` — Postman collection

Regenerate after changing routes/requests/controllers:

```bash
php artisan scribe:generate
```

## Deployment (shared hosting)

No Octane, no standalone Redis — cache and queue both use the `database` driver. On every deploy:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link   # first deploy only
```

Queue jobs (OTP SMS, etc.) need a single cron entry hitting the scheduler every minute; the
scheduler is already configured (`routes/console.php`) to drain the queue via
`queue:work --stop-when-empty` rather than running a long-lived daemon:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## Project status

Built in phases (see `docs/ARCHITECTURE.md` for the full breakdown): auth & accounts, catalog,
cart & checkout, admin ops, growth features (coupons/reviews/wishlist/banners), and dashboard
analytics are all implemented and tested. Deliberately deferred: a real SMS gateway, courier
integrations, and an online payment gateway (SSLCommerz/bKash/Nagad) — these need a provider
chosen before wiring them in.
