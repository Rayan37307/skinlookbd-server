#!/usr/bin/env bash
#
# Redeploy skinlookserver to shared hosting (see docs/DEPLOYMENT.md).
# Run this from your LOCAL machine — it builds assets locally (no Node on the
# server) and then SSHes in to pull, install, migrate and re-cache.
#
# First-time setup: fill in the four variables below, then:
#   chmod +x deploy.sh
#   ./deploy.sh
#
# Usage:
#   ./deploy.sh            # full deploy: build assets, upload, migrate, cache
#   ./deploy.sh --no-assets  # skip the local npm build + asset upload
#   ./deploy.sh --seed       # also run `php artisan db:seed` after migrating

set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")"

# ---- fill these in once -----------------------------------------------------
SSH_USER="skinrkip"                      # your cPanel/SSH username
SSH_HOST="business97.web-hosting.com"                   # server hostname or IP
SSH_PORT="21098"                            # SSH port (cPanel/Namecheap often isn't 22)
REMOTE_PATH="/home/skinrkip/admapi.skinlookbd.com/skinlookbd-server"              # app root on the server (not public_html)
PHP_BIN="php"                               # plain `php` on the PATH over SSH
# -----------------------------------------------------------------------------

WITH_ASSETS=1
WITH_SEED=0

for arg in "$@"; do
  case "$arg" in
    --no-assets) WITH_ASSETS=0 ;;
    --seed) WITH_SEED=1 ;;
    *) echo "Unknown option: $arg" >&2; exit 1 ;;
  esac
done

step() { printf '\n\033[1;34m==> %s\033[0m\n' "$1"; }

if [[ "$WITH_ASSETS" -eq 1 ]]; then
  step "Building frontend assets locally (Node never runs on the server)"
  npm ci
  npm run build

  step "Packaging public/build for upload"
  tar -czf build.tar.gz -C public build

  step "Uploading build.tar.gz to the server"
  scp -P "$SSH_PORT" build.tar.gz "${SSH_USER}@${SSH_HOST}:${REMOTE_PATH}/build.tar.gz"
  rm build.tar.gz
else
  step "Skipping asset build (--no-assets)"
fi

step "Running remote deploy steps over SSH"
ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" bash -s -- "$REMOTE_PATH" "$PHP_BIN" "$WITH_SEED" "$WITH_ASSETS" < deploy-remote.sh

step "Deploy complete"
