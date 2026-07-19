#!/usr/bin/env bash
#
# Run this ON THE SERVER, after you've SSH'd in yourself (cPanel Terminal, PuTTY, etc.):
#
#   cd /home/skinrkip/admapi.skinlookbd.com/skinlookbd-server
#   ./deploy.sh            # pull, install, migrate, re-cache
#   ./deploy.sh --seed     # ...and also run db:seed afterwards
#
# Does NOT touch frontend assets (no Node.js here) — if a change included frontend/CSS/JS
# work, build `public/build/` locally first and upload it separately before running this.

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

PHP_BIN="php"   # plain `php` on the PATH over SSH

WITH_SEED=0
for arg in "$@"; do
  case "$arg" in
    --seed) WITH_SEED=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

step "Entering maintenance mode"
"$PHP_BIN" artisan down --retry=30 || true

step "git pull"
git pull

step "composer install"
composer install --no-dev --optimize-autoloader

step "Clearing stale caches"
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear

step "Running migrations"
"$PHP_BIN" artisan migrate --force

if [[ "$WITH_SEED" -eq 1 ]]; then
  step "Seeding"
  "$PHP_BIN" artisan db:seed --force
fi

step "Rebuilding caches"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

step "Leaving maintenance mode"
"$PHP_BIN" artisan up

step "Done"
