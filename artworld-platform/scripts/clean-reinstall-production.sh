#!/usr/bin/env bash
# Clean APP reinstall on art-VMware20-1 — preserves DB, .env, uploads media,
# SSL (/etc/letsencrypt), and nginx sites artworld-api + artworld-admin.
# Never DROP database. Never chmod 777. File Server down does not block.
set -euo pipefail

REMOTE_PATH="${PROD_PATH:-/var/www/artworld-platform}"
BRANCH="${DEPLOY_BRANCH:-cursor/web-unified-platform-0ab6}"
REPO_URL="${REPO_URL:-https://github.com/berkferdi/artworld.git}"
TS=$(date +%Y%m%d-%H%M%S)
REPORT=/tmp/artworld-clean-reinstall-${TS}.txt
exec > >(tee -a "$REPORT") 2>&1

echo "===== CLEAN REINSTALL START $(date -u) ====="
hostname; whoami; pwd
HOST_NOW=$(hostname)
if [[ "$HOST_NOW" != "art-VMware20-1" && "${FORCE_HOST_OK:-0}" != "1" ]]; then
  echo "WRONG_HOST: $HOST_NOW (need art-VMware20-1)"
  exit 3
fi
test -d "$REMOTE_PATH" || { echo "FAIL: missing $REMOTE_PATH"; exit 3; }

echo "===== 1) Protect env + inventory ====="
PROT="/tmp/artworld-env-protect-${TS}"
mkdir -p "$PROT"
for f in "$REMOTE_PATH/.env" "$REMOTE_PATH/backend/.env" "$REMOTE_PATH/web/.env"; do
  if [[ -f "$f" ]]; then cp -a "$f" "$PROT/"; echo "preserved:$f"; fi
done
# Key names only
for f in "$PROT"/*; do
  [[ -f "$f" ]] || continue
  echo "--- keys $(basename "$f") ---"
  grep -E '^[A-Z0-9_]+=' "$f" | cut -d= -f1 | sort
done
ls -lah "$REMOTE_PATH" | head -40
ls -lah /etc/nginx/sites-available/ 2>/dev/null || true
ls -lah /etc/nginx/sites-enabled/ 2>/dev/null || true
# NEVER delete these
test -f /etc/nginx/sites-available/artworld-api && echo "KEEP:artworld-api"
test -f /etc/nginx/sites-available/artworld-admin && echo "KEEP:artworld-admin"
test -d /etc/letsencrypt && echo "KEEP:letsencrypt"

echo "===== 2) Fresh app tarball (optional) ====="
if [[ -d /home/art ]] && [[ -w /home/art || $(id -u) -eq 0 ]]; then
  FRESH="/home/art/artworld-platform-pre-clean-${TS}.tar.gz"
  sudo tar -czf "$FRESH" \
    --exclude='artworld-platform/web/storage/cache' \
    --exclude='artworld-platform/mobile/build' \
    --exclude='artworld-platform/mobile/.dart_tool' \
    -C /var/www artworld-platform || echo "FRESH_BACKUP_WARN"
  ls -lah "$FRESH" 2>/dev/null || true
fi

echo "===== 3) Stage latest code ====="
STAGING="${STAGING_SRC:-}"
if [[ -z "$STAGING" ]]; then
  STAGING="/tmp/artworld-staging-${TS}"
  mkdir -p "$STAGING"
  if [[ -d /workspace/artworld-platform/backend && -d /workspace/artworld-platform/web ]]; then
    STAGING=/workspace/artworld-platform
    echo "Using /workspace/artworld-platform"
  else
    git clone --depth 1 --branch "$BRANCH" "$REPO_URL" "$STAGING/repo"
    if [[ -d "$STAGING/repo/artworld-platform" ]]; then
      STAGING="$STAGING/repo/artworld-platform"
    else
      STAGING="$STAGING/repo"
    fi
  fi
fi
test -d "$STAGING/backend" && test -d "$STAGING/web"
echo "STAGING=$STAGING"

echo "===== 4) Clean + rsync app (keep env + uploads media) ====="
# Ensure upload dirs exist before rsync exclude
sudo mkdir -p \
  "$REMOTE_PATH/backend/uploads/images" \
  "$REMOTE_PATH/backend/uploads/videos" \
  "$REMOTE_PATH/backend/uploads/thumbnails"

sudo rsync -a --delete \
  --exclude '.env' \
  --exclude 'backend/.env' \
  --exclude 'web/.env' \
  --exclude 'backend/uploads/images/' \
  --exclude 'backend/uploads/videos/' \
  --exclude 'backend/uploads/thumbnails/' \
  --exclude 'web/storage/cache/' \
  --exclude 'mobile/build/' \
  --exclude 'mobile/.dart_tool/' \
  --exclude 'mobile/android/.gradle/' \
  --exclude '.git/' \
  "$STAGING/" "$REMOTE_PATH/"

# Restore env if missing
for name in .env backend/.env web/.env; do
  base=$(basename "$name")
  if [[ ! -f "$REMOTE_PATH/$name" && -f "$PROT/$base" ]]; then
    mkdir -p "$(dirname "$REMOTE_PATH/$name")"
    cp -a "$PROT/$base" "$REMOTE_PATH/$name"
    echo "restored:$name"
  fi
done
test -f "$REMOTE_PATH/backend/.env" || test -f "$REMOTE_PATH/.env"

# APP_DEBUG=false only
for f in "$REMOTE_PATH/backend/.env" "$REMOTE_PATH/.env" "$REMOTE_PATH/web/.env"; do
  [[ -f "$f" ]] || continue
  if grep -qE '^APP_DEBUG=' "$f"; then sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' "$f"
  else echo 'APP_DEBUG=false' >> "$f"; fi
done

# Merge missing keys from examples (never overwrite)
merge_missing() {
  local example="$1" dest="$2"
  [[ -f "$example" && -f "$dest" ]] || return 0
  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ -z "${line// }" ]] && continue
    key="${line%%=*}"; [[ -z "$key" ]] && continue
    if ! grep -qE "^${key}=" "$dest"; then
      [[ "$key" == "APP_DEBUG" ]] && echo "APP_DEBUG=false" >> "$dest" || echo "$line" >> "$dest"
      echo "added_key:$key"
    fi
  done < "$example"
}
merge_missing "$REMOTE_PATH/backend/.env.example" "$REMOTE_PATH/backend/.env"
merge_missing "$REMOTE_PATH/web/.env.example" "$REMOTE_PATH/web/.env"

echo "===== 5) Composer (if present) ====="
if [[ -f "$REMOTE_PATH/backend/composer.json" ]] && command -v composer >/dev/null; then
  (cd "$REMOTE_PATH/backend" && composer install --no-dev --optimize-autoloader --no-interaction) || \
  (cd "$REMOTE_PATH/backend" && composer install --no-interaction) || true
fi
if [[ -f "$REMOTE_PATH/composer.json" ]] && command -v composer >/dev/null; then
  (cd "$REMOTE_PATH" && composer install --no-dev --optimize-autoloader --no-interaction) || true
fi

echo "===== 6) DB ping + safe migrations (no DROP) ====="
set -a
# shellcheck disable=SC1091
if [[ -f "$REMOTE_PATH/backend/.env" ]]; then . "$REMOTE_PATH/backend/.env"
elif [[ -f "$REMOTE_PATH/.env" ]]; then . "$REMOTE_PATH/.env"
fi
set +a
mysql -h "${DB_HOST:-localhost}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  -e "SELECT 1 AS db_ok;" >/dev/null
echo "DB_OK"
for mig in \
  "$REMOTE_PATH/database/migrations/002_web_unified_extensions.sql" \
  "$REMOTE_PATH/database/migrations/003_video_source_types.sql"
do
  [[ -f "$mig" ]] || continue
  echo "Applying $(basename "$mig")"
  mysql -h "${DB_HOST:-localhost}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$mig"
done
echo "MIGRATIONS_OK"

echo "===== 7) PHP lint ====="
cd "$REMOTE_PATH"
find backend web -name '*.php' -print0 | xargs -0 -n1 php -l | tee /tmp/artworld-php-lint.txt
if grep -v 'No syntax errors' /tmp/artworld-php-lint.txt | grep -q .; then
  echo "PHP_LINT_FAIL"; exit 1
fi
echo "PHP_LINT_OK"

echo "===== 8) Permissions ====="
if id www-data >/dev/null 2>&1; then
  if id art >/dev/null 2>&1; then
    sudo chown -R art:www-data "$REMOTE_PATH/backend/uploads" || true
  else
    sudo chown -R www-data:www-data "$REMOTE_PATH/backend/uploads" || true
  fi
  sudo find "$REMOTE_PATH/backend/uploads" -type d -exec chmod 775 {} \;
  sudo find "$REMOTE_PATH/backend/uploads" -type f -exec chmod 664 {} \;
  sudo -u www-data touch "$REMOTE_PATH/backend/uploads/images/.write-test"
  sudo -u www-data rm -f "$REMOTE_PATH/backend/uploads/images/.write-test"
  echo "UPLOADS_WRITE_OK"
fi
# Web/backend readable by www-data
sudo find "$REMOTE_PATH/web" "$REMOTE_PATH/backend/public" "$REMOTE_PATH/backend/admin" \
  -type d -exec chmod 755 {} \; 2>/dev/null || true

echo "===== 9) Nginx inspect (minimum fix; never delete artworld-api/admin) ====="
# Confirm protected files still exist
test -f /etc/nginx/sites-available/artworld-api
test -f /etc/nginx/sites-available/artworld-admin
echo "--- artworld-api root hints ---"
grep -nE 'root |alias |server_name|ssl_certificate' /etc/nginx/sites-available/artworld-api | head -40 || true
echo "--- artworld-admin root hints ---"
grep -nE 'root |alias |server_name|ssl_certificate' /etc/nginx/sites-available/artworld-admin | head -40 || true
# Frontend site (do not delete api/admin). Fix root + /assets/ alias only.
fix_frontend_assets_alias() {
  local cfg="$1"
  if grep -qE 'location\s+[=^~]*\s*/assets/' "$cfg"; then
    echo "assets_location_present:$cfg"
    return 0
  fi
  sudo cp -a "$cfg" "${cfg}.backup-${TS}"
  # Insert assets alias before first "location /" block inside server
  sudo python3 - <<PY
from pathlib import Path
p = Path("$cfg")
text = p.read_text()
needle = "location / {"
alias_block = """    location /assets/ {
        alias /var/www/artworld-platform/web/assets/;
        expires 7d;
        access_log off;
    }

"""
if "web/assets/" in text and "location /assets/" in text:
    print("already_ok")
elif needle in text:
    text = text.replace(needle, alias_block + "    " + needle, 1)
    p.write_text(text)
    print("inserted_assets_alias")
else:
    print("no_location_slash_found")
PY
}

for cfg in /etc/nginx/sites-available/*; do
  base=$(basename "$cfg")
  [[ "$base" == "artworld-api" || "$base" == "artworld-admin" ]] && continue
  if grep -qE 'server_name[^;]*artworldapi\.com\.tr' "$cfg" 2>/dev/null && \
     ! grep -qE 'server_name[^;]*(api|admin)\.artworldapi' "$cfg" 2>/dev/null; then
    echo "FRONTEND_CANDIDATE:$cfg"
    grep -nE 'root |alias |server_name|location' "$cfg" | head -40 || true
    if grep -qE 'root\s+/var/www/artworld-platform/backend/admin' "$cfg"; then
      sudo cp -a "$cfg" "${cfg}.backup-root-${TS}"
      sudo sed -i 's|root\s\+/var/www/artworld-platform/backend/admin;|root /var/www/artworld-platform/web/public;|' "$cfg"
      echo "FIXED_ROOT_TO_WEB_PUBLIC:$cfg"
    fi
    fix_frontend_assets_alias "$cfg"
  fi
done

# Also fix enabled symlinks' targets that are apex-only
for cfg in /etc/nginx/sites-enabled/*; do
  real=$(readlink -f "$cfg" 2>/dev/null || true)
  [[ -n "$real" && -f "$real" ]] || continue
  base=$(basename "$real")
  [[ "$base" == "artworld-api" || "$base" == "artworld-admin" ]] && continue
  if grep -qE 'server_name[^;]*artworldapi\.com\.tr' "$real" && \
     ! grep -qE 'server_name[^;]*(api|admin)\.artworldapi' "$real"; then
    fix_frontend_assets_alias "$real"
  fi
done

echo "EXPECTED: api -> backend/public | admin -> backend/admin | web -> web/public + /assets/ alias"

sudo nginx -t
echo "NGINX_T_OK"

echo "===== 10) PHP-FPM ====="
for u in php8.3-fpm php8.2-fpm php-fpm; do
  if systemctl list-unit-files "${u}.service" 2>/dev/null | grep -q "$u"; then
    sudo systemctl status "$u" --no-pager | head -15 || true
    sudo systemctl reload "$u" || sudo systemctl restart "$u"
    echo "PHP_FPM_OK:$u"
    break
  fi
done

echo "===== 11) Reload nginx ====="
sudo systemctl reload nginx
echo "NGINX_RELOADED"

echo "===== 12) Smoke tests ====="
pass() { echo "PASS:$*"; }
fail() { echo "FAIL:$*"; }

code=$(curl -sk -o /tmp/aw.out -w '%{http_code}' --max-time 20 https://artworldapi.com.tr/ || echo 000)
title=$(grep -oP '(?<=<title>)[^<]+' /tmp/aw.out 2>/dev/null | head -1 || true)
echo "WEB /$code title=$title"
[[ "$code" == "200" && "$title" != *"Giriş"* ]] && pass WEB || fail WEB

code=$(curl -sk -o /tmp/aw.out -w '%{http_code}' --max-time 20 -L https://admin.artworldapi.com.tr/ || echo 000)
echo "ADMIN /$code"
[[ "$code" =~ ^(200|302)$ ]] && pass ADMIN || fail ADMIN

API_OK=1
for e in home news videos programs live; do
  code=$(curl -sk -o /tmp/aw.out -w '%{http_code}' --max-time 20 "https://api.artworldapi.com.tr/api/v1/$e" || echo 000)
  echo "API $e -> $code"
  [[ "$code" == "200" ]] || API_OK=0
done
[[ "$API_OK" == "1" ]] && pass API || fail API

# HTTP→HTTPS
for h in artworldapi.com.tr api.artworldapi.com.tr admin.artworldapi.com.tr; do
  loc=$(curl -sI --max-time 10 "http://$h/" | grep -i '^Location:' | tr -d '\r' || true)
  echo "HTTP_REDIRECT $h -> $loc"
done

# SSL present (do not modify)
for h in artworldapi.com.tr api.artworldapi.com.tr admin.artworldapi.com.tr; do
  echo | openssl s_client -servername "$h" -connect "$h:443" 2>/dev/null | openssl x509 -noout -subject -dates 2>/dev/null | head -5 || echo "SSL_CHECK_WARN:$h"
done

echo "===== CLEAN REINSTALL FINISHED $(date -u) ====="
echo "REPORT=$REPORT"
echo "ENV_PROTECT=$PROT"
