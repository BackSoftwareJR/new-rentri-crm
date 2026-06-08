#!/usr/bin/env bash
# Hostinger: sincronizza public/ Laravel → public_html/ (document root)
# Eseguire dalla root del dominio: ~/domains/demolisci.backsoftware.it

set -euo pipefail

DOMAIN_ROOT="${DOMAIN_ROOT:-$HOME/domains/demolisci.backsoftware.it}"
APP_DIR="${APP_DIR:-$DOMAIN_ROOT/new-rentri-crm}"
PUBLIC_DIR="${PUBLIC_DIR:-$DOMAIN_ROOT/public_html}"

if [[ ! -d "$APP_DIR/public" ]]; then
  echo "ERRORE: $APP_DIR/public non trovato. Esegui prima git clone in new-rentri-crm."
  exit 1
fi

mkdir -p "$PUBLIC_DIR"

# Backup index.php Hostinger default (solo prima volta)
if [[ -f "$PUBLIC_DIR/index.php" ]] && [[ ! -f "$PUBLIC_DIR/index.php.hostinger.bak" ]]; then
  cp "$PUBLIC_DIR/index.php" "$PUBLIC_DIR/index.php.hostinger.bak"
fi

echo "→ Sync asset pubblici (build, favicon, robots, .htaccess)..."
rsync -av --delete \
  --exclude 'index.php' \
  --exclude 'storage' \
  "$APP_DIR/public/" "$PUBLIC_DIR/"

echo "→ Install index.php Hostinger (punta a ../new-rentri-crm)..."
cp "$APP_DIR/deploy/hostinger/public/index.php" "$PUBLIC_DIR/index.php"

echo "→ Symlink storage pubblico..."
rm -rf "$PUBLIC_DIR/storage"
ln -sfn "../new-rentri-crm/storage/app/public" "$PUBLIC_DIR/storage"

echo "→ Permessi storage Laravel..."
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo ""
echo "OK. Struttura:"
echo "  App (privata):  $APP_DIR"
echo "  Web root:       $PUBLIC_DIR"
echo ""
echo "Verifica: curl -I https://demolisci.backsoftware.it/up"
