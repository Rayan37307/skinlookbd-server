# Redeploying

Quick reference for `deploy.sh`. For first-time server setup (creating the MySQL database, PHP
version, cron, etc.), see `docs/DEPLOYMENT.md` instead — this is just for repeat deploys once
that's done.

## Workflow

You SSH into the server yourself, then run the script there:

```bash
ssh -p 21098 skinrkip@business97.web-hosting.com
cd /home/skinrkip/admapi.skinlookbd.com/skinlookbd-server
./deploy.sh
```

It does, in order: enters maintenance mode, `git pull`, `composer install --no-dev`, clears
stale caches, runs migrations, rebuilds caches, leaves maintenance mode.

## Options

| Command              | What it does                                                    |
|-----------------------|------------------------------------------------------------------|
| `./deploy.sh`         | Pull, install, migrate, re-cache                                  |
| `./deploy.sh --seed`  | ...and also run `php artisan db:seed --force` afterwards          |

Use `--seed` when there's new seed data to load (tags, labels, the product catalog, etc.) onto a
database that doesn't have it yet — **not** on a database that already has real orders/customers,
since reseeding will duplicate rows or hit unique-constraint errors.

## Frontend assets

This script does not touch `public/build/` — there's no Node.js on the server. If a change
included frontend/CSS/JS work, build it locally first (`npm ci && npm run build`) and upload the
resulting `public/build/` directory separately (FTP/File Manager, or `scp`) before running
`./deploy.sh`. Pure backend/PHP changes (routes, controllers, migrations, seeders, Filament) need
no asset step at all.

## Before you run it

- Commit and push your changes first — the server does `git pull`, so it only gets what's already
  on the remote.

## If something goes wrong mid-deploy

The site is left in maintenance mode until the very end. If a step fails partway through, you're
already SSH'd in — check what state it's in:

```bash
tail -n 50 storage/logs/laravel.log
php artisan up   # bring the site back if it's stuck in maintenance mode
```

See `docs/DEPLOYMENT.md`'s Troubleshooting section for common causes (stale cache, wrong PHP
version, missing extensions).
