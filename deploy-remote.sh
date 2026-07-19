#!/usr/bin/env bash
#
# Runs ON THE SERVER (over SSH) — piped in and executed by deploy.sh / deploy.bat.
# Not meant to be run directly. Args: REMOTE_PATH PHP_BIN WITH_SEED WITH_ASSETS

set -euo pipefail
REMOTE_PATH="$1"
PHP_BIN="$2"
WITH_SEED="$3"
WITH_ASSETS="$4"

cd "$REMOTE_PATH"

echo "--> Entering maintenance mode"
"$PHP_BIN" artisan down --retry=30 || true

echo "--> git pull"
git pull

if [[ "$WITH_ASSETS" -eq 1 ]]; then
  echo "--> extracting uploaded frontend assets"
  rm -rf public/build
  mkdir -p public/build
  tar -xzf build.tar.gz -C public
  rm build.tar.gz
fi

echo "--> composer install"
composer install --no-dev --optimize-autoloader

echo "--> clearing stale caches"
"$PHP_BIN" artisan config:clear
"$PHP_BIN" artisan route:clear
"$PHP_BIN" artisan view:clear

echo "--> running migrations"
"$PHP_BIN" artisan migrate --force

if [[ "$WITH_SEED" -eq 1 ]]; then
  echo "--> seeding"
  "$PHP_BIN" artisan db:seed --force
fi

echo "--> rebuilding caches"
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache

echo "--> leaving maintenance mode"
"$PHP_BIN" artisan up

echo "--> done"
