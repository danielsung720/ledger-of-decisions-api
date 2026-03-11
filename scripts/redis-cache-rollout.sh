#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
API_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
cd "$API_DIR"

ACTION=""
ENV_FILE=".env"
MODE="auto" # auto | docker | local
API_URL="http://localhost:8080/up"
ASSUME_YES=0
SKIP_CHECK=0
SKIP_RESTART=0
DRY_RUN=0
REDIS_HOST=""
REDIS_PORT=""
REDIS_PASSWORD=""
RESTART_CMD=""

usage() {
  cat <<'EOF'
Usage: ./scripts/redis-cache-rollout.sh <apply|rollback|status> [options]

Actions:
  apply               Switch cache store to redis
  rollback            Switch cache store back to database
  status              Show current cache-related env values

Options:
  --env-file <path>   Target env file (default: .env)
  --docker            Prefer docker mode for checks/restart
  --local             Prefer local mode for checks/restart
  --api-url <url>     Health endpoint URL (default: http://localhost:8080/up)
  --redis-host <val>  Override REDIS_HOST in env file on apply
  --redis-port <val>  Override REDIS_PORT in env file on apply
  --redis-password <val>
                      Override REDIS_PASSWORD in env file on apply
  --restart-cmd <cmd> Custom restart command after env change
  --skip-restart      Do not restart services after env change
  --skip-check        Do not run redis-cache-healthcheck after action
  --dry-run           Print intended changes only (no file write)
  --yes, -y           Skip confirmation prompt
  -h, --help          Show this help message

Examples:
  ./scripts/redis-cache-rollout.sh apply --yes --docker
  ./scripts/redis-cache-rollout.sh apply --redis-host 10.0.0.5 --restart-cmd "sudo systemctl restart php8.4-fpm nginx"
  ./scripts/redis-cache-rollout.sh rollback --yes --skip-check
EOF
}

if [ "$#" -eq 0 ]; then
  usage
  exit 1
fi

ACTION="$1"
shift

case "$ACTION" in
  apply|rollback|status) ;;
  *)
    echo "Unsupported action: $ACTION" >&2
    usage
    exit 1
    ;;
esac

while [ "$#" -gt 0 ]; do
  case "$1" in
    --env-file)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --env-file" >&2
        exit 1
      fi
      ENV_FILE="$2"
      shift
      ;;
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
    --redis-host)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --redis-host" >&2
        exit 1
      fi
      REDIS_HOST="$2"
      shift
      ;;
    --redis-port)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --redis-port" >&2
        exit 1
      fi
      REDIS_PORT="$2"
      shift
      ;;
    --redis-password)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --redis-password" >&2
        exit 1
      fi
      REDIS_PASSWORD="$2"
      shift
      ;;
    --restart-cmd)
      if [ "${2:-}" = "" ]; then
        echo "Missing value for --restart-cmd" >&2
        exit 1
      fi
      RESTART_CMD="$2"
      shift
      ;;
    --skip-restart)
      SKIP_RESTART=1
      ;;
    --skip-check)
      SKIP_CHECK=1
      ;;
    --dry-run)
      DRY_RUN=1
      ;;
    --yes|-y)
      ASSUME_YES=1
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      exit 1
      ;;
  esac
  shift
done

if [ ! -f "$ENV_FILE" ]; then
  echo "Env file not found: $ENV_FILE" >&2
  exit 1
fi

get_env_value() {
  key="$1"
  awk -F= -v key="$key" '$1==key {print substr($0, index($0,$2)); exit}' "$ENV_FILE"
}

upsert_env_value() {
  key="$1"
  value="$2"
  tmp_file=$(mktemp)
  awk -v key="$key" -v value="$value" '
    BEGIN {updated = 0}
    index($0, key"=") == 1 {
      if (updated == 0) {
        print key"="value
        updated = 1
      }
      next
    }
    {print}
    END {
      if (updated == 0) {
        print key"="value
      }
    }
  ' "$ENV_FILE" > "$tmp_file"
  mv "$tmp_file" "$ENV_FILE"
}

print_status() {
  echo "ENV_FILE=$ENV_FILE"
  echo "APP_ENV=$(get_env_value APP_ENV || true)"
  echo "CACHE_STORE=$(get_env_value CACHE_STORE || true)"
  echo "CACHE_STORE_FALLBACK=$(get_env_value CACHE_STORE_FALLBACK || true)"
  echo "REDIS_HOST=$(get_env_value REDIS_HOST || true)"
  echo "REDIS_PORT=$(get_env_value REDIS_PORT || true)"
  echo "REDIS_PASSWORD=$(get_env_value REDIS_PASSWORD || true)"
}

run_restart() {
  if [ "$SKIP_RESTART" -eq 1 ]; then
    echo "[skip] restart skipped."
    return
  fi

  if [ -n "$RESTART_CMD" ]; then
    echo "[run] $RESTART_CMD"
    sh -lc "$RESTART_CMD"
    return
  fi

  if [ "$MODE" = "docker" ]; then
    docker compose restart app nginx
    return
  fi

  if [ "$MODE" = "local" ]; then
    echo "[warn] local mode without --restart-cmd; restart not executed."
    return
  fi

  if docker compose ps --services --status running 2>/dev/null | grep -qx "app"; then
    docker compose restart app nginx
  else
    echo "[warn] auto mode did not detect docker app; restart not executed."
  fi
}

run_healthcheck() {
  if [ "$SKIP_CHECK" -eq 1 ]; then
    echo "[skip] healthcheck skipped."
    return
  fi

  if [ "$MODE" = "docker" ]; then
    ./scripts/redis-cache-healthcheck.sh --docker --api-url "$API_URL"
  elif [ "$MODE" = "local" ]; then
    ./scripts/redis-cache-healthcheck.sh --local --api-url "$API_URL"
  else
    ./scripts/redis-cache-healthcheck.sh --api-url "$API_URL"
  fi
}

confirm_action() {
  if [ "$ASSUME_YES" -eq 1 ]; then
    return
  fi

  echo "You are about to run '$ACTION' on $ENV_FILE"
  printf "Type 'CONFIRM' to continue: "
  read -r confirm
  if [ "$confirm" != "CONFIRM" ]; then
    echo "Cancelled."
    exit 1
  fi
}

create_backup() {
  backup_file="${ENV_FILE}.backup.redis-rollout.$(date +%Y%m%d%H%M%S)"
  cp "$ENV_FILE" "$backup_file"
  echo "[backup] $backup_file"
}

if [ "$ACTION" = "status" ]; then
  print_status
  exit 0
fi

confirm_action

if [ "$DRY_RUN" -eq 1 ]; then
  echo "[dry-run] action=$ACTION env_file=$ENV_FILE"
  if [ "$ACTION" = "apply" ]; then
    echo "  set CACHE_STORE=redis"
    echo "  set CACHE_STORE_FALLBACK=database"
    [ -n "$REDIS_HOST" ] && echo "  set REDIS_HOST=$REDIS_HOST"
    [ -n "$REDIS_PORT" ] && echo "  set REDIS_PORT=$REDIS_PORT"
    [ -n "$REDIS_PASSWORD" ] && echo "  set REDIS_PASSWORD=<masked>"
  else
    echo "  set CACHE_STORE=database"
    echo "  set CACHE_STORE_FALLBACK=database"
  fi
  exit 0
fi

create_backup

if [ "$ACTION" = "apply" ]; then
  upsert_env_value CACHE_STORE redis
  upsert_env_value CACHE_STORE_FALLBACK database
  [ -n "$REDIS_HOST" ] && upsert_env_value REDIS_HOST "$REDIS_HOST"
  [ -n "$REDIS_PORT" ] && upsert_env_value REDIS_PORT "$REDIS_PORT"
  [ -n "$REDIS_PASSWORD" ] && upsert_env_value REDIS_PASSWORD "$REDIS_PASSWORD"
else
  upsert_env_value CACHE_STORE database
  upsert_env_value CACHE_STORE_FALLBACK database
fi

print_status
run_restart
run_healthcheck

echo "Redis cache rollout action '$ACTION' completed."
