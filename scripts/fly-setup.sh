#!/bin/sh
# fly-setup.sh - One-time Fly.io initialization
# Run this ONCE before first deployment.
# Usage: sh scripts/fly-setup.sh

set -e

echo "=== Fly.io First-Time Setup ==="

# 1. Create the app (skip if already created)
echo "\n[1/5] Creating app..."
fly launch --no-deploy --copy-config || echo "App may already exist, continuing..."

# 2. Create managed Postgres
echo "\n[2/5] Creating Postgres..."
fly postgres create --name ledger-db
fly postgres attach ledger-db

# 3. Create managed Redis (Upstash)
echo "\n[3/5] Creating Redis..."
fly redis create --name ledger-redis

echo "\n[4/5] Setting secrets..."
echo "Please enter the following values:\n"

printf "APP_KEY (run 'php artisan key:generate --show' to generate): "
read APP_KEY
fly secrets set APP_KEY="$APP_KEY"

printf "DB_HOST: "
read DB_HOST
printf "DB_PORT (default 5432): "
read DB_PORT
DB_PORT=${DB_PORT:-5432}
printf "DB_DATABASE: "
read DB_DATABASE
printf "DB_USERNAME: "
read DB_USERNAME
printf "DB_PASSWORD: "
read DB_PASSWORD

fly secrets set \
  DB_HOST="$DB_HOST" \
  DB_PORT="$DB_PORT" \
  DB_DATABASE="$DB_DATABASE" \
  DB_USERNAME="$DB_USERNAME" \
  DB_PASSWORD="$DB_PASSWORD"

printf "REDIS_HOST: "
read REDIS_HOST
printf "REDIS_PASSWORD: "
read REDIS_PASSWORD

fly secrets set \
  REDIS_HOST="$REDIS_HOST" \
  REDIS_PASSWORD="$REDIS_PASSWORD"

printf "RESEND_API_KEY (leave blank to skip): "
read RESEND_API_KEY
if [ -n "$RESEND_API_KEY" ]; then
  fly secrets set RESEND_API_KEY="$RESEND_API_KEY"
fi

echo "\n[5/5] Setup complete. Run 'sh scripts/fly-deploy.sh' to deploy."
