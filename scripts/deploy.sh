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

echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo -e "${BLUE}  RENTRI CRM — Deploy${NC}"
echo -e "${BLUE}═══════════════════════════════════════════${NC}"
echo ""

step "1/11 — Salvataggio commit corrente (rollback)"
git rev-parse HEAD > "$DEPLOY_MARKER"
ok "Commit salvato in $DEPLOY_MARKER ($(cat "$DEPLOY_MARKER"))"

step "2/11 — Maintenance mode"
php artisan down --retry=60 --refresh=15 || fail "Impossibile attivare maintenance mode"
ok "Maintenance mode ON"

step "3/11 — Pull latest code"
git pull --ff-only || fail "git pull fallito"
ok "Codice aggiornato ($(git rev-parse --short HEAD))"

step "4/11 — Composer install (production)"
composer install --no-dev --optimize-autoloader --no-interaction || fail "composer install fallito"
ok "Dipendenze PHP installate"

step "5/11 — Frontend build"
if [[ -f package-lock.json ]]; then
  npm ci && npm run build || fail "npm ci/build fallito"
else
  npm install && npm run build || fail "npm install/build fallito"
fi
ok "Asset frontend compilati"

step "6/11 — Database migrations"
php artisan migrate --force || fail "migrate fallito"
ok "Migrazioni applicate"

step "7/11 — Seed siti (solo se tabella vuota)"
SITI_COUNT="$(php artisan tinker --execute="echo \\App\\Models\\Sito::count();" 2>/dev/null | tail -1 | tr -d '[:space:]')"
if [[ "$SITI_COUNT" == "0" ]]; then
  php artisan db:seed --class=SitoSeeder --force || fail "SitoSeeder fallito"
  ok "SitoSeeder eseguito (tabella siti era vuota)"
else
  ok "Siti già presenti ($SITI_COUNT) — seed saltato"
fi

step "8/11 — Cache config/route/view/event"
php artisan config:cache || fail "config:cache fallito"
php artisan route:cache || fail "route:cache fallito"
php artisan view:cache || fail "view:cache fallito"
php artisan event:cache || fail "event:cache fallito"
ok "Cache applicative rigenerate"

step "9/11 — Cache warm (post-deploy)"
php artisan cache:warm || fail "cache:warm fallito"
ok "Cache pre-riscaldata"

step "10/11 — Restart queue workers"
php artisan horizon:terminate || fail "horizon:terminate fallito"
ok "Horizon terminate inviato (workers in restart)"

step "11/11 — Disattivazione maintenance + smoke check"
php artisan up || fail "Impossibile disattivare maintenance mode"
ok "Maintenance mode OFF"

php artisan rentri:preflight || fail "rentri:preflight fallito — verificare checklist"
ok "Preflight RENTRI OK"

echo ""
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
echo -e "${GREEN}  Deploy completato con successo${NC}"
echo -e "${GREEN}═══════════════════════════════════════════${NC}"
