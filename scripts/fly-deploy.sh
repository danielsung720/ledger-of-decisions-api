#!/bin/sh
# fly-deploy.sh - Deploy to Fly.io and run migrations
# Usage: sh scripts/fly-deploy.sh

set -e

echo "=== Deploying to Fly.io ==="

# 1. Deploy
echo "\n[1/3] Building and deploying..."
fly deploy --dockerfile Dockerfile.fly

# 2. Run migrations
echo "\n[2/3] Running migrations..."
fly ssh console --command "php artisan migrate --force"

# 3. Clear caches
echo "\n[3/3] Clearing caches..."
fly ssh console --command "php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo "\nDone. Check status: fly status"
