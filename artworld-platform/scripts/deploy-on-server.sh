#!/usr/bin/env bash
# Local production cutover — run ONLY on art-VMware20-1 when cwd is
# /var/www/artworld-platform (or PROD_PATH). Does not delete .env, uploads,
# database, SSL certs, or the named backup archive. Never uses chmod 777.
#
# Optional:
#   STAGING_SRC   Path to new artworld-platform tree (default: git worktree checkout)
#   SKIP_NGINX=1  Skip nginx config apply (still runs nginx -t)
#   SKIP_CERTBOT=1
set -euo pipefail

REMOTE_PATH="${PROD_PATH:-/var/www/artworld-platform}"
BACKUP_PATH="${PROD_BACKUP:-/home/art/artworld-platform-backup-20260925-173634.tar.gz}"
BRANCH="${DEPLOY_BRANCH:-cursor/web-unified-platform-0ab6}"
REPORT=/tmp/artworld-cutover-report-$(date +%Y%m%d-%H%M%S).txt
exec > >(tee -a "$REPORT") 2>&1

echo "===== CUTOVER START $(date -u) ====="
echo "REPORT=$REPORT"

echo "===== PHASE 1: inventory ====="
pwd; whoami; hostname
ls -lah "$REMOTE_PATH" | head -40
du -sh "$REMOTE_PATH"
ls -lah "$REMOTE_PATH/backend" | head -40
ls -lah "$REMOTE_PATH/backend/uploads" | head -40
git -C "$REMOTE_PATH" status 2>/dev/null || true
git -C "$REMOTE_PATH" remote -v 2>/dev/null || true
git -C "$REMOTE_PATH" branch --show-current 2>/dev/null || true

echo "===== PHASE 2: verify backup ====="
if [[ ! -f "$BACKUP_PATH" ]]; then
  echo "BACKUP_FAIL: missing $BACKUP_PATH"
  exit 2
fi
sudo tar -tzf "$BACKUP_PATH" >/dev/null
echo "BACKUP_OK"

echo "===== PHASE 3: protect secrets ====="
TS=$(date +%Y%m%d-%H%M%S)
PROT="/tmp/artworld-deploy-protect-$TS"
mkdir -p "$PROT"
for f in "$REMOTE_PATH/.env" "$REMOTE_PATH/backend/.env" "$REMOTE_PATH/web/.env"; do
  if [[ -f "$f" ]]; then
    cp -a "$f" "$PROT/"
    echo "preserved:$f"
  fi
done
echo "PROTECT_DIR=$PROT"

# Key names only (never print values)
list_env_keys() {
  local file="$1"
  [[ -f "$file" ]] || return 0
  echo "--- keys in $file ---"
  for k in APP_ENV APP_DEBUG APP_URL APP_KEY DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD \
           MEDIA_URL FILE_SERVER_ENABLED FILE_SERVER_MODE FILE_SERVER_HOST FILE_SERVER_PORT \
           FILE_SERVER_USER FILE_SERVER_PASSWORD FILE_SERVER_SSH_KEY FILE_SERVER_BASE_PATH \
           FILE_SERVER_PUBLIC_BASE_URL FILE_SERVER_UPLOAD_URL FILE_SERVER_UPLOAD_TOKEN \
           YOUTUBE_API_KEY YOUTUBE_CHANNEL_ID API_BASE_URL; do
    if grep -qE "^${k}=" "$file" 2>/dev/null; then
      if [[ "$k" == "APP_KEY" ]]; then
        echo "  $k=PRESENT"
      elif [[ "$k" == "APP_DEBUG" ]]; then
        val=$(grep -E "^APP_DEBUG=" "$file" | head -1 | cut -d= -f2- | tr -d '"' | tr -d "'")
        echo "  APP_DEBUG=$val"
      else
        echo "  $k=SET"
      fi
    else
      echo "  $k=MISSING"
    fi
  done
}
list_env_keys "$REMOTE_PATH/backend/.env"
list_env_keys "$REMOTE_PATH/.env"
list_env_keys "$REMOTE_PATH/web/.env"

# Force APP_DEBUG=false only
force_debug_false() {
  local file="$1"
  [[ -f "$file" ]] || return 0
  if grep -qE '^APP_DEBUG=' "$file"; then
    sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' "$file"
  else
    echo 'APP_DEBUG=false' >> "$file"
  fi
  echo "APP_DEBUG forced false in $file"
}
force_debug_false "$REMOTE_PATH/backend/.env"
force_debug_false "$REMOTE_PATH/.env"
force_debug_false "$REMOTE_PATH/web/.env"

echo "===== PHASE 4: transfer code ====="
STAGING="${STAGING_SRC:-}"
if [[ -z "$STAGING" ]]; then
  STAGING="/tmp/artworld-staging-$TS"
  mkdir -p "$STAGING"
  if [[ -d /workspace/artworld-platform ]]; then
    STAGING=/workspace/artworld-platform
    echo "Using /workspace/artworld-platform as staging source"
  elif git -C "$REMOTE_PATH" rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "Fetching $BRANCH into staging clone..."
    git clone --depth 1 --branch "$BRANCH" "$(git -C "$REMOTE_PATH" remote get-url origin)" "$STAGING/repo" || \
      git -C "$REMOTE_PATH" fetch origin "$BRANCH" && git -C "$REMOTE_PATH" archive "origin/$BRANCH:artworld-platform" | tar -x -C "$STAGING"
    if [[ -d "$STAGING/repo/artworld-platform" ]]; then
      STAGING="$STAGING/repo/artworld-platform"
    elif [[ -d "$STAGING/repo" ]] && [[ -d "$STAGING/repo/backend" ]]; then
      STAGING="$STAGING/repo"
    fi
  else
    echo "FAIL: no staging source; set STAGING_SRC"
    exit 1
  fi
fi

echo "RSYNC from $STAGING -> $REMOTE_PATH"
sudo rsync -a \
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

# Restore .env if wiped
for name in .env backend/.env web/.env; do
  base=$(basename "$name")
  if [[ ! -f "$REMOTE_PATH/$name" ]] && [[ -f "$PROT/$base" ]]; then
    mkdir -p "$(dirname "$REMOTE_PATH/$name")"
    cp -a "$PROT/$base" "$REMOTE_PATH/$name"
    echo "restored:$name"
  fi
done
test -f "$REMOTE_PATH/backend/.env" || test -f "$REMOTE_PATH/.env"
echo "ENV_PRESENT"

echo "===== PHASE 5: env merge (add missing keys only) ====="
merge_missing_keys() {
  local example="$1" dest="$2"
  [[ -f "$example" && -f "$dest" ]] || return 0
  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    [[ -z "${line// }" ]] && continue
    key="${line%%=*}"
    [[ -z "$key" ]] && continue
    if ! grep -qE "^${key}=" "$dest"; then
      if [[ "$key" == "APP_DEBUG" ]]; then
        echo "APP_DEBUG=false" >> "$dest"
      else
        echo "$line" >> "$dest"
      fi
      echo "added_key:$key -> $dest"
    fi
  done < "$example"
  force_debug_false "$dest"
}
merge_missing_keys "$REMOTE_PATH/backend/.env.example" "$REMOTE_PATH/backend/.env"
merge_missing_keys "$REMOTE_PATH/web/.env.example" "$REMOTE_PATH/web/.env"

echo "===== PHASE 6: database migrations ====="
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
  echo "Applying $(basename "$mig")"
  mysql -h "${DB_HOST:-localhost}" -P "${DB_PORT:-3306}" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" < "$mig"
done
echo "MIGRATIONS_OK"

echo "===== PHASE 7: PHP syntax ====="
cd "$REMOTE_PATH"
find backend web -name '*.php' -print0 | xargs -0 -n1 php -l | tee /tmp/artworld-php-lint.txt
if grep -v 'No syntax errors' /tmp/artworld-php-lint.txt | grep -q .; then
  echo "PHP_LINT_FAIL"
  exit 1
fi
echo "PHP_LINT_OK"

echo "===== PHASE 8: uploads permissions ====="
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

echo "===== PHASE 9: File Server reachability ====="
FS_HOST="${FILE_SERVER_HOST:-193.35.155.55}"
if timeout 5 bash -c "echo >/dev/tcp/${FS_HOST}/22" 2>/dev/null; then
  echo "FILE_SERVER_REACHABLE=$FS_HOST"
else
  echo "FILE_SERVER_UNREACHABLE=$FS_HOST (keeping local fallback)"
fi

echo "===== PHASE 10-15: nginx + HTTPS ====="
NGINX_AVAIL=/etc/nginx/sites-available
NGINX_EN=/etc/nginx/sites-enabled
echo "sites-available:"; ls -lah "$NGINX_AVAIL" 2>/dev/null || true
echo "sites-enabled:"; ls -lah "$NGINX_EN" 2>/dev/null || true

# Backup existing artworld-related configs
for cfg in "$NGINX_AVAIL"/*artworld* "$NGINX_AVAIL"/*artworldapi*; do
  [[ -f "$cfg" ]] || continue
  sudo cp -a "$cfg" "${cfg}.backup-$TS"
  echo "backed_up:$cfg"
done

DOC_CONF="$REMOTE_PATH/docs/nginx/artworldapi.com.tr.conf.md"
if [[ "${SKIP_NGINX:-0}" != "1" ]] && [[ -f "$DOC_CONF" ]]; then
  # Extract fenced nginx block from markdown into a staging file for operator review
  awk '/^```nginx/{flag=1;next}/^```/{if(flag){exit}}flag' "$DOC_CONF" > "/tmp/artworldapi.com.tr.conf.candidate"
  echo "Wrote /tmp/artworldapi.com.tr.conf.candidate (from docs). Apply manually if safe."
fi

sudo nginx -t
echo "NGINX_T_OK"
# Reload only after -t passes. Do not auto-overwrite live site without operator intent.
if [[ "${APPLY_NGINX_RELOAD:-0}" == "1" ]]; then
  sudo systemctl reload nginx
  echo "NGINX_RELOADED"
else
  echo "NGINX_RELOAD_SKIPPED (set APPLY_NGINX_RELOAD=1 after installing unified conf)"
fi

if [[ "${SKIP_CERTBOT:-1}" != "1" ]]; then
  # Attempt apex cert without breaking existing api certs
  if command -v certbot >/dev/null; then
    sudo certbot certificates 2>/dev/null | head -80 || true
    echo "CERTBOT_NOTE: run certbot --nginx -d artworldapi.com.tr -d www.artworldapi.com.tr only if apex CN is wrong"
  fi
fi

echo "===== PHASE 16-21: smoke tests ====="
HOST_APEX="${SMOKE_HOST:-artworldapi.com.tr}"
for path in / /api/v1/home /api/v1/news /api/v1/videos /api/v1/programs /api/v1/live /login; do
  code=$(curl -sk -o /tmp/aw-smoke.out -w '%{http_code}' --max-time 20 "https://${HOST_APEX}${path}" || echo 000)
  echo "HTTPS ${path} -> ${code}"
done
for e in home news videos programs live; do
  code=$(curl -sk -o /tmp/aw-smoke.out -w '%{http_code}' --max-time 20 "https://api.${HOST_APEX}/api/v1/${e}" || echo 000)
  echo "API_SUB ${e} -> ${code}"
done

echo "===== CUTOVER FINISHED $(date -u) ====="
echo "REPORT=$REPORT"
