#!/usr/bin/env bash
# Safe production deploy for Art World Unified Platform.
# Does NOT delete uploads, .env, or the named backup archive.
#
# Required env:
#   PROD_SSH_PRIVATE_KEY  (PEM contents)
#   PROD_SSH_USER         (e.g. art)
# Optional:
#   PROD_SSH_HOST         (default: artworldapi.com.tr)
#   PROD_SSH_PORT         (default: 22)
#   PROD_PATH             (default: /var/www/artworld-platform)
#   PROD_BACKUP           (default: /home/art/artworld-platform-backup-20260925-173634.tar.gz)
#
# Usage (from repo root):
#   ./artworld-platform/scripts/deploy-production.sh

set -euo pipefail

HOST="${PROD_SSH_HOST:-artworldapi.com.tr}"
PORT="${PROD_SSH_PORT:-22}"
USER_NAME="${PROD_SSH_USER:?PROD_SSH_USER required}"
REMOTE_PATH="${PROD_PATH:-/var/www/artworld-platform}"
BACKUP_PATH="${PROD_BACKUP:-/home/art/artworld-platform-backup-20260925-173634.tar.gz}"
KEY_FILE="$(mktemp)"
SSH_OPTS=(-i "$KEY_FILE" -p "$PORT" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new -o BatchMode=yes)
cleanup() { rm -f "$KEY_FILE"; }
trap cleanup EXIT

if [[ -z "${PROD_SSH_PRIVATE_KEY:-}" ]]; then
  echo "FAIL: PROD_SSH_PRIVATE_KEY is empty" >&2
  exit 1
fi

printf '%s\n' "$PROD_SSH_PRIVATE_KEY" > "$KEY_FILE"
chmod 600 "$KEY_FILE"

SSH=(ssh "${SSH_OPTS[@]}" "${USER_NAME}@${HOST}")
RSYNC_SSH="ssh ${SSH_OPTS[*]}"

echo "===== PHASE 1: remote inventory ====="
"${SSH[@]}" 'set -e; whoami; hostname; pwd; ls -lah /var/www/artworld-platform | head; du -sh /var/www/artworld-platform; ls -lah /var/www/artworld-platform/backend | head; ls -lah /var/www/artworld-platform/backend/uploads | head; git -C /var/www/artworld-platform status 2>/dev/null || true; git -C /var/www/artworld-platform remote -v 2>/dev/null || true; git -C /var/www/artworld-platform branch --show-current 2>/dev/null || true'

echo "===== PHASE 2: verify backup ====="
"${SSH[@]}" "set -e; test -f '$BACKUP_PATH'; sudo tar -tzf '$BACKUP_PATH' >/dev/null; echo BACKUP_OK; ls -lah '$BACKUP_PATH'"

echo "===== PHASE 3: protect secrets/uploads snapshot ====="
"${SSH[@]}" "set -e
  TS=\$(date +%Y%m%d-%H%M%S)
  mkdir -p /tmp/artworld-deploy-protect-\$TS
  for f in $REMOTE_PATH/.env $REMOTE_PATH/backend/.env $REMOTE_PATH/web/.env; do
    if [ -f \"\$f\" ]; then cp -a \"\$f\" /tmp/artworld-deploy-protect-\$TS/; echo preserved:\$f; fi
  done
  echo PROTECT_DIR=/tmp/artworld-deploy-protect-\$TS
"

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SRC="$ROOT/artworld-platform"
if [[ ! -d "$SRC" ]]; then
  echo "FAIL: source not found at $SRC" >&2
  exit 1
fi

echo "===== PHASE 4: rsync code (exclude .env + uploads media) ====="
rsync -az --delete \
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
  -e "$RSYNC_SSH" \
  "$SRC/" "${USER_NAME}@${HOST}:${REMOTE_PATH}/"

echo "===== PHASE 5: restore .env if wiped ====="
"${SSH[@]}" "set -e
  # restore any accidentally missing env from protect dir (newest)
  PROT=\$(ls -1dt /tmp/artworld-deploy-protect-* 2>/dev/null | head -1)
  if [ -n \"\$PROT\" ]; then
    for name in .env backend/.env web/.env; do
      base=\$(basename \"\$name\")
      if [ ! -f $REMOTE_PATH/\$name ] && [ -f \"\$PROT/\$base\" ]; then
        mkdir -p \$(dirname $REMOTE_PATH/\$name)
        cp -a \"\$PROT/\$base\" $REMOTE_PATH/\$name
        echo restored:\$name
      fi
    done
  fi
  test -f $REMOTE_PATH/backend/.env || test -f $REMOTE_PATH/.env
  echo ENV_PRESENT
"

echo "===== PHASE 6: permissions + write test ====="
"${SSH[@]}" "set -e
  sudo chown -R art:www-data $REMOTE_PATH/backend/uploads || sudo chown -R www-data:www-data $REMOTE_PATH/backend/uploads || true
  sudo find $REMOTE_PATH/backend/uploads -type d -exec chmod 775 {} \;
  sudo -u www-data touch $REMOTE_PATH/backend/uploads/images/.write-test
  sudo -u www-data rm -f $REMOTE_PATH/backend/uploads/images/.write-test
  echo UPLOADS_WRITE_OK
"

echo "===== PHASE 7: PHP syntax ====="
"${SSH[@]}" "set -e
  cd $REMOTE_PATH
  find backend web -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/artworld-php-lint.txt
  ! grep -v 'No syntax errors' /tmp/artworld-php-lint.txt
  echo PHP_LINT_OK
"

echo "===== PHASE 8: migrations (idempotent) ====="
"${SSH[@]}" "set -e
  cd $REMOTE_PATH
  # load DB creds without printing secrets
  set -a
  if [ -f backend/.env ]; then . backend/.env; elif [ -f .env ]; then . .env; fi
  set +a
  mysql -h \"\${DB_HOST:-localhost}\" -P \"\${DB_PORT:-3306}\" -u \"\$DB_USERNAME\" -p\"\$DB_PASSWORD\" \"\$DB_DATABASE\" \
    < database/migrations/002_web_unified_extensions.sql
  mysql -h \"\${DB_HOST:-localhost}\" -P \"\${DB_PORT:-3306}\" -u \"\$DB_USERNAME\" -p\"\$DB_PASSWORD\" \"\$DB_DATABASE\" \
    < database/migrations/003_video_source_types.sql
  echo MIGRATIONS_OK
"

echo "===== PHASE 9: nginx check (no reload unless -t passes) ====="
"${SSH[@]}" "set -e
  sudo nginx -t
  echo NGINX_T_OK
  # Do not auto-replace site config; operator applies docs/nginx/artworldapi.com.tr.conf.md intentionally.
"

echo "===== PHASE 10: smoke ====="
for path in / /api/v1/home /api/v1/news /api/v1/videos /api/v1/programs /api/v1/live /login; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "https://${HOST}${path}" || echo 000)
  echo "HTTPS ${path} -> ${code}"
done

# Prefer apex API; fall back to api. subdomain
for e in home news videos programs live; do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "https://api.${HOST}/api/v1/${e}" || echo 000)
  echo "API_SUB ${e} -> ${code}"
done

echo "DEPLOY_SCRIPT_FINISHED"
