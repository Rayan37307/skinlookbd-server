# Deployment: shared hosting

This app is built for cPanel-style shared hosting (Namecheap Stellar/Stellar Plus/Business,
or equivalent): Apache or LiteSpeed in front of PHP-FPM, MySQL, no persistent app server, no
Redis, no Node.js at runtime. Cache and queue both use the `database` driver; the queue is
drained by cron instead of a long-lived worker (see `routes/console.php`).

Two paths are covered below because shared hosting plans differ in what they expose:

- **[Path A — SSH/Terminal available](#path-a--ssh-or-cpanel-terminal-available)** (recommended):
  Composer and Artisan run directly on the server.
- **[Path B — FTP/File Manager only](#path-b--ftpfile-manager-only-no-shell-access)**: everything
  that needs a shell (`composer install`, `npm run build`, `artisan`) runs on your machine first,
  and you upload the results.

Check which you have before starting: cPanel → look for a "Terminal" icon, or ask your host
whether SSH is enabled for your plan. Namecheap enables SSH on request even for Stellar plans
(Support ticket → "enable SSH access").

## 0. Prerequisites

- A domain/subdomain pointed at the hosting account.
- cPanel → **MySQL Databases**: create a database, a user, a strong password, and attach the user
  to the database with **All Privileges**. Note the three values — the DB name and username are
  usually prefixed with your cPanel username (e.g. `cpaneluser_skinlook`).
- cPanel → **MultiPHP Manager** (or **Select PHP Version**): set PHP to **8.4** (minimum required
  by `composer.json` is `^8.3`) for the domain. Confirm these extensions are enabled in **Select
  PHP Version → Extensions**: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`,
  `bcmath`, `fileinfo`, `curl`, `zip`, `intl`. Missing `pdo_mysql`/`mbstring`/`fileinfo` is the
  most common cause of a blank 500 on first boot.
- Locally: PHP 8.4 + Composer, and Node/npm to build frontend assets (Filament's compiled CSS/JS
  live in `public/build` and must be committed/uploaded — there's no Node on the server).

## 1. Document root — get `public/` to serve, not the repo root

Shared hosting serves whatever sits in `public_html/`. Laravel's entry point is `public/index.php`,
and everything else (`app/`, `.env`, `vendor/`, etc.) must **not** be web-accessible. Two options,
in order of preference:

**Option 1 — separate app directory (best).** If your host lets you change the document root
(cPanel → **Domains** → edit the domain's document root, or an addon-domain setting), put the
whole repo in `~/skinlookserver` (outside `public_html`) and point the document root at
`~/skinlookserver/public`. Nothing else needs to change; `public/.htaccess` and `public/index.php`
already assume this layout.

**Option 2 — document root is fixed to `public_html/` (most Stellar-tier plans).** Put the app
above the web root and copy `public/`'s *contents* into `public_html/`, then repoint the two
`require`/`file_exists` paths in the copied `index.php` up one extra level:

```
~/skinlookserver/           <- app, app/, vendor/, .env, etc. (NOT web-accessible)
~/public_html/              <- contents of public/, deployed here
    index.php                  (edited, see below)
    .htaccess
    build/, css/, js/, ...
```

Edit `~/public_html/index.php` (the copy, not the source in the repo) so the two `__DIR__/..`
references become `__DIR__/../skinlookserver`:

```php
require __DIR__.'/../skinlookserver/vendor/autoload.php';
$app = require_once __DIR__.'/../skinlookserver/bootstrap/app.php';
```

and the maintenance-mode check a few lines above it the same way. Everything else in
`public_html` (the `.htaccess`, `build/`, `css/`, `js/`, `favicon.ico`, `robots.txt`, `docs/`)
is copied as-is with no edits. Re-copy `index.php` and re-apply this edit every deploy (or script
it — see [§6](#6-repeat-deploys)).

The rest of this guide assumes the app root is `~/skinlookserver` (adjust paths if you used a
different directory name).

## 2. Get the code onto the server

**Path A (SSH):**

```bash
ssh youruser@yourhost.com
git clone <your-repo-url> skinlookserver   # or upload+unzip if the repo isn't pushed anywhere reachable
cd skinlookserver
```

**Path B (no SSH):** zip the repo locally (exclude `.git`, `node_modules`, `vendor` — vendor gets
built next and uploaded separately since Composer needs to run somewhere with the right PHP
version; building it locally with the same PHP version as the host works fine for pure-PHP
dependencies, which this project only has), then upload and extract via cPanel **File Manager**.

## 3. Install dependencies

**Path A:**

```bash
composer install --no-dev --optimize-autoloader
```

**Path B (locally, then upload):**

```bash
composer install --no-dev --optimize-autoloader
```

then upload the resulting `vendor/` directory into `~/skinlookserver/vendor` (zip it locally,
upload the zip via File Manager, extract there — uploading thousands of individual files over
FTP is painfully slow).

## 4. Build frontend assets (both paths, always local — no Node on the server)

```bash
npm ci
npm run build
```

Upload the generated `public/build/` directory to wherever `public/` ends up on the server
(`~/skinlookserver/public/build` for Option 1, or `~/public_html/build` for Option 2).

## 5. Configure the environment

Copy `.env.example` to `.env` in the app root and fill in production values:

```bash
cp .env.example .env
```

```dotenv
APP_NAME="SkinLookBD"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpaneluser_skinlook
DB_USERNAME=cpaneluser_skinlook
DB_PASSWORD="<the password you set in MySQL Databases>"

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD="<mailbox password, create one in cPanel Email Accounts>"
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Leave `APP_KEY` blank for now — generated in the next step. Do **not** set `APP_DEBUG=true` in
production; it leaks stack traces (including `.env` values in some error pages) to any visitor.

`.env` must live in `~/skinlookserver/.env` (outside the web root) either way — never let it end
up in `public_html`.

## 6. Run the deploy commands

**Path A** — run directly on the server:

```bash
php artisan key:generate --force   # only if APP_KEY is still blank
php artisan migrate --force
php artisan storage:link           # first deploy only
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Path B** — run the same commands locally against a MySQL instance configured with the
*production* `.env` values (a temporary local MySQL pointed at the same schema works, or tunnel
to the remote DB if your host allows external MySQL connections), then upload the results:

- `bootstrap/cache/*.php` (from `config:cache`/`route:cache`) — upload as-is.
- `storage/framework/views/*` (from `view:cache`) — upload as-is.
- Migrations are the one step that's awkward without a shell: either enable **Remote MySQL** in
  cPanel temporarily and run `php artisan migrate --force` locally against the production
  database, or use **phpMyAdmin** to import a schema dump generated locally
  (`php artisan schema:dump` isn't set up here, so the Remote MySQL route is simpler).
- `storage:link` creates a symlink (`public/storage → storage/app/public`); most FTP clients
  can't create symlinks. If your host's File Manager supports "Create Symlink," use it; otherwise
  skip this until you have shell access, or serve uploaded product images from a different disk
  (S3/R2 — already supported via the `filesystems.php` `s3` disk, just unused by default).

## Storage & permissions

`storage/` and `bootstrap/cache/` must be writable by the PHP process:

```bash
chmod -R 775 storage bootstrap/cache
```

If PHP runs as a different user than your shell user (uncommon on cPanel, but check if you get
permission-denied errors in `storage/logs/laravel.log`), you may need `chmod -R 777` on those two
directories specifically — nothing else needs it.

## Cron (queue + scheduler)

The app has no long-running queue worker; `routes/console.php` schedules
`queue:work --stop-when-empty` to run every minute via cron, so a single cron entry drives both
the Laravel scheduler and queue processing. cPanel → **Cron Jobs** → add, every minute:

```
* * * * * /usr/local/bin/php84 /home/cpaneluser/skinlookserver/artisan schedule:run >> /dev/null 2>&1
```

The PHP binary path varies by host — run `which php` or `which php84` over SSH to confirm, or
check **Select PHP Version** in cPanel for the CLI binary path. If you're on Path B without shell
access, cPanel's Cron Jobs page usually shows available PHP binary paths in a dropdown when
adding the job.

## HTTPS

Enable **AutoSSL** in cPanel (usually on by default for the primary domain). Once the certificate
is issued, `APP_URL` should already be `https://...` (set in step 5) — Laravel uses it for
generated URLs, and Sanctum/session cookies need it to match the real scheme for secure cookies
to work correctly.

## 6. Repeat deploys

Frontend assets are always built **locally** (or in CI), never on the shared host — see the note
at the top of [§4](#4-build-frontend-assets-both-paths-always-local--no-node-on-the-server). Node
is a build-time tool only; once `npm run build` produces `public/build/*.{js,css}`, those are
static files served by Apache/LiteSpeed like any other asset, so the PHP host never needs Node
installed. If frontend code changed, run this locally first and upload the resulting
`public/build/` directory before the SSH steps below:

```bash
npm ci && npm run build
```

Then, each subsequent deploy (Path A, SSH):

```bash
cd ~/skinlookserver
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

(matches the root `README.md` "Deployment (shared hosting)" section). On Path B, repeat §2–§6
above for whatever changed, re-applying the `index.php` path edit from §1 if you used Option 2 and
re-copied a fresh `public/index.php`.

## Troubleshooting

- **Blank page / generic 500, nothing in `storage/logs/laravel.log`**: usually a PHP version or
  missing-extension issue (see [§0](#0-prerequisites)), or `storage`/`bootstrap/cache` not
  writable. Temporarily set `APP_DEBUG=true` and `php artisan config:clear` to see the real error,
  then revert.
- **"could not find driver" on any DB query**: `pdo_mysql` extension not enabled for the selected
  PHP version — enable it in **Select PHP Version → Extensions**.
- **"Specified key was too long; max key length is 1000 bytes"** on `migrate`: the server's
  default MySQL/MariaDB storage engine is MyISAM (1000-byte key limit) instead of InnoDB (767 or
  3072 bytes depending on config) — fixed in this app by forcing `'engine' => 'InnoDB'` in
  `config/database.php`'s `mysql`/`mariadb` connections, so a fresh `composer install` + this repo
  as-is should no longer hit it. If it recurs, confirm InnoDB is actually available on the host
  (`SHOW ENGINES;` via phpMyAdmin).
- **500 immediately after deploy but was working before**: stale cache from `config:cache` /
  `route:cache` referencing old code — `php artisan config:clear && php artisan route:clear` then
  re-cache.
- **Migrations time out**: shared hosting PHP-FPM often caps `max_execution_time` (30–60s) for
  web requests, but CLI (`artisan migrate` over SSH or cron) usually isn't capped the same way —
  run migrations from a shell/cron context, not by hitting a web endpoint.
- **Uploaded product images 404**: `storage:link` didn't run or the symlink didn't survive the
  upload (some FTP tools silently skip symlinks, or convert them to empty files) — recreate it via
  SSH (`php artisan storage:link`) or your host's File Manager symlink feature.
