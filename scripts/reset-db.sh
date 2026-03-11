#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
API_DIR=$(CDPATH= cd -- "$SCRIPT_DIR/.." && pwd)
cd "$API_DIR"

USE_SEED=0
ASSUME_YES=0
MODE="auto" # auto | docker | local

while [ "$#" -gt 0 ]; do
  case "$1" in
    --seed)
      USE_SEED=1
      ;;
    --yes|-y)
      ASSUME_YES=1
      ;;
    --docker)
      MODE="docker"
      ;;
    --local)
      MODE="local"
      ;;
    -h|--help)
      cat <<'EOF'
Usage: ./scripts/reset-db.sh [--seed] [--yes] [--docker|--local]

This will DROP all tables and recreate schema via Laravel migration.
Primary keys (auto-increment / sequences) will be reset.

Options:
  --seed        Run database seeders after migrate:fresh
  --yes, -y     Skip confirmation prompt
  --docker      Force running in Docker container (service: app)
  --local       Force running on local machine
  --help, -h    Show this help message
EOF
      exit 0
      ;;
    *)
      echo "Unknown option: $1" >&2
      echo "Use --help to see available options." >&2
      exit 1
      ;;
  esac
  shift
done

if [ "$ASSUME_YES" -ne 1 ]; then
  echo "WARNING: This will delete ALL database data and reset primary keys."
  printf "Type 'RESET' to continue: "
  read -r CONFIRM
  if [ "$CONFIRM" != "RESET" ]; then
    echo "Cancelled."
    exit 1
  fi
fi

MIGRATE_CMD="php artisan migrate:fresh --force"
if [ "$USE_SEED" -eq 1 ]; then
  MIGRATE_CMD="$MIGRATE_CMD --seed"
fi

run_in_docker() {
  docker compose exec -T app sh -lc "$MIGRATE_CMD"
}

run_local() {
  sh -lc "$MIGRATE_CMD"
}

if [ "$MODE" = "docker" ]; then
  run_in_docker
elif [ "$MODE" = "local" ]; then
  run_local
else
  if docker compose ps --services --status running 2>/dev/null | grep -qx "app"; then
    run_in_docker
  else
    run_local
  fi
fi

echo "Database reset completed."
