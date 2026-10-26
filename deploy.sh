cat > deploy.sh <<'BASH'
#!/usr/bin/env bash
set -Eeuo pipefail

# Paths for your hosting environment
PHP="/opt/alt/php84/usr/bin/php"
COMPOSER="/opt/cpanel/ea-wappspector/composer.phar"
PROJECT_DIR="$HOME/admapi.skinlookbd.com"

cd "$PROJECT_DIR"

echo "======================================"
echo " Laravel deployment - PHP 8.4"
echo "======================================"

# Verify required files and binaries
[[ -x "$PHP" ]] || { echo "ERROR: PHP 8.4 not found: $PHP"; exit 1; }
[[ -f "$COMPOSER" ]] || { echo "ERROR: Composer PHAR not found: $COMPOSER"; exit 1; }
[[ -f artisan && -f composer.json ]] || {
    echo "ERROR: Not in the Laravel project root."
    exit 1
}

echo "[1/7] PHP version"
"$PHP" -v | head -n 2

echo "[2/7] Creating Laravel cache and storage directories"
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Ensure Laravel's compiled-view directory exists
if grep -q 'VIEW_COMPILED_PATH' .env 2>/dev/null; then
    echo "Using VIEW_COMPILED_PATH from .env"
fi

echo "[3/7] Installing production dependencies"
"$PHP" "$COMPOSER" install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

echo "[4/7] Checking environment configuration"
if [[ ! -f .env ]]; then
    if [[ -f .env.example ]]; then
        cp .env.example .env
        echo "Created .env from .env.example."
        echo "IMPORTANT: Configure .env and rerun this script."
        exit 1
    else
        echo "ERROR: Neither .env nor .env.example exists."
        exit 1
    fi
fi

echo "[5/7] Clearing Laravel caches"
"$PHP" artisan optimize:clear

echo "[6/7] Rebuilding Laravel caches"
"$PHP" artisan package:discover
"$PHP" artisan config:cache
"$PHP" artisan view:cache

# Route caching can fail if the app has conflicting route definitions.
if "$PHP" artisan route:cache; then
    echo "Route cache created."
else
    echo "WARNING: Route caching failed; check your routes."
fi

echo "[7/7] Deployment checks"
"$PHP" artisan --version

echo "======================================"
echo " Deployment commands completed."
echo "======================================"
BASH

chmod +x deploy.sh