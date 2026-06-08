#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DEPLOY_MARKER=".deploy-last-commit"

step() {
  echo -e "${BLUE}▶${NC} ${YELLOW}$1${NC}"
}

ok() {
  echo -e "${GREEN}✓${NC} $1"
}

fail() {
  echo -e "${RED}✗${NC} $1" >&2
  exit 1
}

if [[ ! -f "$DEPLOY_MARKER" ]]; then
  fail "File $DEPLOY_MARKER assente — eseguire prima scripts/deploy.sh"
fi

PREV_COMMIT="$(cat "$DEPLOY_MARKER")"

echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${BLUE}  RENTRI CRM — Rollback${NC}"
echo -e "${BLUE}  Target: ${PREV_COMMIT}${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo ""

step "1/7 — Maintenance mode"
php artisan down --retry=60 --refresh=15 || fail "Impossibile attivare maintenance mode"
ok "Maintenance mode ON"

step "2/7 — Git reset al commit pre-deploy"
git reset --hard "$PREV_COMMIT" || fail "git reset fallito"
ok "Codice ripristinato a $PREV_COMMIT"

step "3/7 — Composer reinstall"
composer install --no-dev --optimize-autoloader --no-interaction || fail "composer install fallito"
ok "Dipendenze PHP reinstallate"

step "4/7 — Frontend rebuild"
if [[ -f package-lock.json ]]; then
  npm ci && npm run build || fail "npm ci/build fallito"
else
  npm install && npm run build || fail "npm install/build fallito"
fi
ok "Asset frontend ricompilati"

step "5/7 — Clear cache"
php artisan config:clear || fail "config:clear fallito"
php artisan route:clear || fail "route:clear fallito"
php artisan view:clear || fail "view:clear fallito"
php artisan event:clear || fail "event:clear fallito"
php artisan cache:clear || fail "cache:clear fallito"
ok "Cache svuotate"

step "6/7 — Restart queue workers"
php artisan horizon:terminate || fail "horizon:terminate fallito"
ok "Horizon terminate inviato"

step "7/7 — Disattivazione maintenance"
php artisan up || fail "Impossibile disattivare maintenance mode"
ok "Maintenance mode OFF"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}  Rollback completato${NC}"
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo -e "${YELLOW}Nota: le migrazioni DB non sono state annullate.${NC}"
echo -e "${YELLOW}Se necessario: php artisan migrate:rollback --step=N${NC}"
