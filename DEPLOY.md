# Redeploying

Manual steps for a repeat deploy. For first-time server setup (creating the MySQL database, PHP
version, cron, etc.), see `docs/DEPLOYMENT.md` instead — this is just for deploys once that's
already done.

## 1. SSH in

```bash
ssh -p 21098 skinrkip@business97.web-hosting.com
cd /home/skinrkip/admapi.skinlookbd.com/skinlookbd-server
```

## 2. Enter maintenance mode

```bash
php artisan down --retry=30
```

## 3. Pull the latest code

```bash
git pull
```

## 4. Install dependencies

```bash
composer install --no-dev --optimize-autoloader
```

## 5. Clear stale caches

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 6. Run migrations

```bash
php artisan migrate --force
```

## 7. (Only if there's new seed data to load)

```bash
php artisan db:seed --force
```

Only do this on a database that doesn't already have real orders/customers — reseeding a live
database duplicates rows or hits unique-constraint errors.

## 8. Rebuild caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 9. Leave maintenance mode

```bash
php artisan up
```

## Frontend assets

None of the above touches `public/build/` — there's no Node.js on the server. If a change
included frontend/CSS/JS work, build it locally first (`npm ci && npm run build`) and upload the
resulting `public/build/` directory separately (FTP/File Manager, or `scp`) before step 2. Pure
backend/PHP changes (routes, controllers, migrations, seeders, Filament) need no asset step.

## If something goes wrong mid-deploy

```bash
tail -n 50 storage/logs/laravel.log
php artisan up   # bring the site back if it's stuck in maintenance mode
```

See `docs/DEPLOYMENT.md`'s Troubleshooting section for common causes (stale cache, wrong PHP
version, missing extensions).
