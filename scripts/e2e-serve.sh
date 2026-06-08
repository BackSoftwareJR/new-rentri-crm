#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

PORT="${E2E_PORT:-8765}"

if [[ ! -f .env ]]; then
  cp .env.example .env
  php artisan key:generate --force --quiet
fi

php artisan migrate --force --quiet
php artisan db:seed --force --quiet

export ALLOW_SESSION_DEMO="${ALLOW_SESSION_DEMO:-true}"

exec php artisan serve --host=127.0.0.1 --port="$PORT"
