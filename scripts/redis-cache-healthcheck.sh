#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
API_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
cd "$API_DIR"

MODE="auto" # auto | docker | local
API_URL="http://localhost:8080/up"
SKIP_API=0
SKIP_CACHE=0

while [ "$#" -gt 0 ]; do
  case "$1" in
    --docker)
      MODE="docker"
      ;;
    --local)
      MODE="local"
      ;;
    --api-url)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --api-url" >&2
        exit 1
      fi
      API_URL="$2"
      shift
      ;;
    --skip-api)
      SKIP_API=1
      ;;
    --skip-cache)
      SKIP_CACHE=1
      ;;
    -h|--help)
      cat <<'EOF'
Usage: ./scripts/redis-cache-healthcheck.sh [options]

Options:
  --docker            Force running artisan check in Docker app container
  --local             Force running artisan check in local environment
  --api-url <url>     Health endpoint URL (default: http://localhost:8080/up)
  --skip-api          Skip HTTP health endpoint check
  --skip-cache        Skip Redis cache round-trip check
  -h, --help          Show this help message

Examples:
  ./scripts/redis-cache-healthcheck.sh --docker
  ./scripts/redis-cache-healthcheck.sh --local --api-url https://staging-api.example.com/up
EOF
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      exit 1
      ;;
  esac
  shift
done

if [ "$SKIP_API" -eq 0 ]; then
  echo "[check] HTTP health endpoint: $API_URL"
  curl -fsS --retry 10 --retry-delay 1 --retry-connrefused "$API_URL" >/dev/null
  echo "[ok] HTTP health endpoint is reachable."
fi

if [ "$SKIP_CACHE" -eq 0 ]; then
  TINKER_EXPR=$(cat <<'EOF'
$key = "redis_rollout_health_".time();
cache()->put($key, "ok", 30);
echo "CACHE_DEFAULT=".config("cache.default").PHP_EOL;
echo "CACHE_VALUE=".cache()->get($key).PHP_EOL;
cache()->forget($key);
EOF
)

  run_cache_check_docker() {
    docker compose exec -T app php artisan tinker --execute="$TINKER_EXPR"
  }

  run_cache_check_local() {
    php artisan tinker --execute="$TINKER_EXPR"
  }

  if [ "$MODE" = "docker" ]; then
    OUTPUT=$(run_cache_check_docker)
  elif [ "$MODE" = "local" ]; then
    OUTPUT=$(run_cache_check_local)
  else
    if docker compose ps --services --status running 2>/dev/null | grep -qx "app"; then
      OUTPUT=$(run_cache_check_docker)
    else
      OUTPUT=$(run_cache_check_local)
    fi
  fi

  echo "$OUTPUT" | grep -q '^CACHE_DEFAULT=redis$'
  echo "$OUTPUT" | grep -q '^CACHE_VALUE=ok$'
  echo "[ok] Redis cache round-trip check passed."
fi

echo "Redis cache healthcheck completed."
