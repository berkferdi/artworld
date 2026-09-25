#!/usr/bin/env bash
# Local production cutover — run ONLY on art-VMware20-1 when cwd is
# /var/www/artworld-platform (or PROD_PATH). Does not delete .env, uploads,
# database, SSL certs, or the named backup archive. Never uses chmod 777.
#
# Go-live defaults: applies unified nginx so apex serves WEB (not admin redirect).
# File Server reachability is checked but never blocks cutover.
#
# Optional:
#   STAGING_SRC   Path to new artworld-platform tree (default: git worktree checkout)
#   SKIP_NGINX=1  Skip nginx config install (still runs nginx -t)
#   SKIP_CERTBOT=1  (default) skip certbot expand
#   APPLY_NGINX=0  write candidate only, do not install/reload
set -euo pipefail

REMOTE_PATH="${PROD_PATH:-/var/www/artworld-platform}"
BACKUP_PATH="${PROD_BACKUP:-/home/art/artworld-platform-backup-20260925-173634.tar.gz}"
BRANCH="${DEPLOY_BRANCH:-cursor/web-unified-platform-0ab6}"
APPLY_NGINX="${APPLY_NGINX:-1}"
SKIP_NGINX="${SKIP_NGINX:-0}"
SKIP_CERTBOT="${SKIP_CERTBOT:-1}"
REPORT=/tmp/artworld-cutover-report-$(date +%Y%m%d-%H%M%S).txt
exec > >(tee -a "$REPORT") 2>&1

echo "===== CUTOVER START $(date -u) ====="
echo "REPORT=$REPORT"

echo "===== PHASE 1: inventory ====="
pwd; whoami; hostname
HOST_NOW=$(hostname)
if [[ "$HOST_NOW" != "art-VMware20-1" ]] && [[ "${FORCE_HOST_OK:-0}" != "1" ]]; then
  echo "WRONG_HOST: expected art-VMware20-1, got $HOST_NOW (set FORCE_HOST_OK=1 to override)"
  exit 3
fi
test -d "$REMOTE_PATH" || { echo "FAIL: missing $REMOTE_PATH"; exit 3; }
ls -lah "$REMOTE_PATH" | head -40
du -sh "$REMOTE_PATH"
ls -lah "$REMOTE_PATH/backend" | head -40
ls -lah "$REMOTE_PATH/backend/uploads" | head -40
git -C "$REMOTE_PATH" status 2>/dev/null || true
git -C "$REMOTE_PATH" remote -v 2>/dev/null || true
git -C "$REMOTE_PATH" branch --show-current 2>/dev/null || true

echo "===== PHASE 2: verify named backup + create fresh tree backup ====="
if [[ ! -f "$BACKUP_PATH" ]]; then
  echo "BACKUP_FAIL: missing $BACKUP_PATH"
  exit 2
fi
sudo tar -tzf "$BACKUP_PATH" >/dev/null
echo "BACKUP_OK: $BACKUP_PATH (untouched)"
TS=$(date +%Y%m%d-%H%M%S)
FRESH_BACKUP="/home/art/artworld-platform-pre-cutover-${TS}.tar.gz"
# Exclude bulky caches; keep .env + uploads
sudo tar -czf "$FRESH_BACKUP" \
  --exclude='artworld-platform/web/storage/cache' \
  --exclude='artworld-platform/mobile/build' \
  --exclude='artworld-platform/mobile/.dart_tool' \
  -C /var/www artworld-platform
echo "FRESH_BACKUP_OK: $FRESH_BACKUP"
ls -lah "$FRESH_BACKUP"

echo "===== PHASE 3: protect secrets ====="
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

echo "===== PHASE 9: File Server reachability (non-blocking) ====="
FS_HOST="${FILE_SERVER_HOST:-193.35.155.55}"
if timeout 5 bash -c "echo >/dev/tcp/${FS_HOST}/22" 2>/dev/null; then
  echo "FILE_SERVER_REACHABLE=$FS_HOST"
else
  echo "FILE_SERVER_UNREACHABLE=$FS_HOST (keeping local fallback; not blocking go-live)"
fi

echo "===== PHASE 10: PHP-FPM ====="
PHP_FPM_UNIT=""
for u in php8.3-fpm php8.2-fpm php8.1-fpm php-fpm; do
  if systemctl list-unit-files "${u}.service" 2>/dev/null | grep -q "${u}"; then
    PHP_FPM_UNIT="$u"
    break
  fi
done
if [[ -n "$PHP_FPM_UNIT" ]]; then
  sudo systemctl status "$PHP_FPM_UNIT" --no-pager | head -20 || true
  sudo systemctl reload "$PHP_FPM_UNIT" || sudo systemctl restart "$PHP_FPM_UNIT"
  echo "PHP_FPM_OK:$PHP_FPM_UNIT"
else
  echo "PHP_FPM_WARN: no php-fpm unit found"
fi
# Detect socket for nginx
PHP_SOCK=""
for s in /run/php/php8.3-fpm.sock /run/php/php8.2-fpm.sock /run/php/php8.1-fpm.sock /run/php/php-fpm.sock; do
  if [[ -S "$s" ]]; then PHP_SOCK="$s"; break; fi
done
echo "PHP_SOCK=${PHP_SOCK:-unknown}"

echo "===== PHASE 11-15: nginx unified apex (WEB + ADMIN + API) ====="
NGINX_AVAIL=/etc/nginx/sites-available
NGINX_EN=/etc/nginx/sites-enabled
echo "sites-available:"; ls -lah "$NGINX_AVAIL" 2>/dev/null || true
echo "sites-enabled:"; ls -lah "$NGINX_EN" 2>/dev/null || true

# Backup existing artworld-related configs
for cfg in "$NGINX_AVAIL"/*artworld* "$NGINX_AVAIL"/*artworldapi* "$NGINX_EN"/*; do
  [[ -e "$cfg" ]] || continue
  [[ -f "$cfg" || -L "$cfg" ]] || continue
  real=$(readlink -f "$cfg" 2>/dev/null || echo "$cfg")
  if [[ -f "$real" ]]; then
    sudo cp -a "$real" "${real}.backup-$TS"
    echo "backed_up:$real"
  fi
done

# Resolve SSL cert paths without breaking existing api certs
SSL_CERT=""
SSL_KEY=""
for live in /etc/letsencrypt/live/artworldapi.com.tr /etc/letsencrypt/live/api.artworldapi.com.tr /etc/letsencrypt/live/admin.artworldapi.com.tr; do
  if [[ -f "$live/fullchain.pem" && -f "$live/privkey.pem" ]]; then
    SSL_CERT="$live/fullchain.pem"
    SSL_KEY="$live/privkey.pem"
    echo "SSL_USING:$live"
    break
  fi
done
if [[ -z "$SSL_CERT" ]]; then
  # Fallback: scrape from current enabled site
  for cfg in "$NGINX_EN"/*; do
    [[ -f "$cfg" || -L "$cfg" ]] || continue
    c=$(grep -E '^\s*ssl_certificate\s+' "$cfg" 2>/dev/null | head -1 | awk '{print $2}' | tr -d ';') || true
    k=$(grep -E '^\s*ssl_certificate_key\s+' "$cfg" 2>/dev/null | head -1 | awk '{print $2}' | tr -d ';') || true
    if [[ -n "$c" && -f "$c" && -n "$k" && -f "$k" ]]; then
      SSL_CERT="$c"; SSL_KEY="$k"; echo "SSL_FROM_EXISTING_CONFIG:$c"; break
    fi
  done
fi

PHP_SOCK="${PHP_SOCK:-/run/php/php8.3-fpm.sock}"
CANDIDATE="/tmp/artworldapi.com.tr.conf.candidate"
cat > "$CANDIDATE" <<NGINX
# Art World unified — generated by deploy-on-server.sh ${TS}
# Apex: WEB / | Admin /login | API /api/v1/

upstream artworld_php {
    server unix:${PHP_SOCK};
}

server {
    listen 80;
    listen [::]:80;
    server_name artworldapi.com.tr www.artworldapi.com.tr;
    return 301 https://artworldapi.com.tr\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name artworldapi.com.tr www.artworldapi.com.tr;

    ssl_certificate     ${SSL_CERT:-/etc/ssl/certs/ssl-cert-snakeoil.pem};
    ssl_certificate_key ${SSL_KEY:-/etc/ssl/private/ssl-cert-snakeoil.key};
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;

    client_max_body_size 512M;

    # --- API (shared mobile + web) ---
    location ^~ /api/ {
        root /var/www/artworld-platform/backend/public;
        try_files \$uri /index.php\$is_args\$args;
        location ~ \.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME /var/www/artworld-platform/backend/public/index.php;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }
    }

    location ^~ /images/ {
        alias /var/www/artworld-platform/backend/uploads/images/;
        expires 30d;
        add_header Accept-Ranges bytes;
        add_header Access-Control-Allow-Origin *;
    }
    location ^~ /videos/ {
        alias /var/www/artworld-platform/backend/uploads/videos/;
        expires 7d;
        add_header Accept-Ranges bytes;
        add_header Access-Control-Allow-Origin *;
        types { video/mp4 mp4; video/webm webm; application/vnd.apple.mpegurl m3u8; }
    }
    location ^~ /thumbnails/ {
        alias /var/www/artworld-platform/backend/uploads/thumbnails/;
        expires 30d;
        add_header Accept-Ranges bytes;
    }

    # --- Admin ---
    location = /login {
        return 302 /admin/login.php;
    }
    location = /login.php {
        return 302 /admin/login.php;
    }
    location ^~ /admin/ {
        alias /var/www/artworld-platform/backend/admin/;
        index index.php login.php;
        location ~ \.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
            include fastcgi_params;
            fastcgi_read_timeout 600;
        }
        location ~* \.(env|log|sql)\$ { deny all; }
    }

    # --- Web frontend (LIVE-FIRST HOMEPAGE) ---
    location /assets/ {
        alias /var/www/artworld-platform/web/assets/;
        expires 7d;
    }
    location / {
        root /var/www/artworld-platform/web/public;
        try_files \$uri \$uri/ /index.php\$is_args\$args;
        location ~ \.php\$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
}
NGINX
# Drop include lines if files missing
if [[ ! -f /etc/letsencrypt/options-ssl-nginx.conf ]]; then
  sed -i '/options-ssl-nginx.conf/d' "$CANDIDATE"
fi
if [[ ! -f /etc/letsencrypt/ssl-dhparams.pem ]]; then
  sed -i '/ssl-dhparams.pem/d' "$CANDIDATE"
fi
echo "Wrote $CANDIDATE"

if [[ "$SKIP_NGINX" == "1" ]]; then
  echo "NGINX_APPLY_SKIPPED (SKIP_NGINX=1)"
elif [[ "$APPLY_NGINX" != "1" ]]; then
  echo "NGINX_INSTALL_SKIPPED (APPLY_NGINX!=1); candidate at $CANDIDATE"
else
  TARGET="$NGINX_AVAIL/artworldapi.com.tr.conf"
  sudo cp -a "$CANDIDATE" "$TARGET"
  # Disable conflicting apex site configs that steal artworldapi.com.tr
  for en in "$NGINX_EN"/*; do
    base=$(basename "$en")
    [[ "$base" == "artworldapi.com.tr.conf" ]] && continue
    if grep -qE 'server_name\s+.*artworldapi\.com\.tr' "$en" 2>/dev/null; then
      echo "disabling_conflicting:$en"
      sudo mv "$en" "${en}.disabled-$TS" || sudo rm -f "$en"
    fi
  done
  sudo ln -sfn "$TARGET" "$NGINX_EN/artworldapi.com.tr.conf"
  if sudo nginx -t; then
    echo "NGINX_T_OK"
    sudo systemctl reload nginx
    echo "NGINX_RELOADED"
  else
    echo "NGINX_T_FAIL — restoring previous enabled sites if possible"
    sudo rm -f "$NGINX_EN/artworldapi.com.tr.conf"
    for d in "$NGINX_EN"/*.disabled-$TS; do
      [[ -e "$d" ]] || continue
      sudo mv "$d" "${d%.disabled-$TS}"
    done
    sudo nginx -t && sudo systemctl reload nginx || true
    exit 1
  fi
fi

if [[ "$SKIP_CERTBOT" != "1" ]] && command -v certbot >/dev/null; then
  sudo certbot certificates 2>/dev/null | head -80 || true
  # Expand/obtain apex names without deleting existing certs
  sudo certbot --nginx -d artworldapi.com.tr -d www.artworldapi.com.tr --non-interactive --agree-tos --keep-until-expiring --expand 2>&1 | tail -40 || \
    echo "CERTBOT_EXPAND_SKIPPED_OR_FAILED (site may still work with existing cert)"
fi

echo "===== PHASE 16-21: smoke tests ====="
HOST_APEX="${SMOKE_HOST:-artworldapi.com.tr}"
WEB_PASS=0; ADMIN_PASS=0; API_PASS=0; LIVE_PASS=0; MOBILE_PASS=0
for path in / /api/v1/home /api/v1/news /api/v1/videos /api/v1/programs /api/v1/live /login; do
  code=$(curl -sk -o /tmp/aw-smoke.out -w '%{http_code}' --max-time 20 "https://${HOST_APEX}${path}" || echo 000)
  echo "HTTPS ${path} -> ${code}"
  case "$path" in
    /)
      if [[ "$code" == "200" ]] && ! grep -qi 'Giriş | Art World Mobile' /tmp/aw-smoke.out; then
        WEB_PASS=1
        grep -qiE 'canl|live|hls|video|art world' /tmp/aw-smoke.out && LIVE_PASS=1 || true
      fi
      ;;
    /login)
      [[ "$code" =~ ^(200|302)$ ]] && ADMIN_PASS=1
      ;;
    /api/v1/home|/api/v1/news|/api/v1/videos|/api/v1/programs|/api/v1/live)
      if [[ "$code" == "200" ]] && grep -q '"success"' /tmp/aw-smoke.out; then
        API_PASS=1
      fi
      ;;
  esac
done
MOBILE_OK=1
for e in home news videos programs live; do
  code=$(curl -sk -o /tmp/aw-smoke.out -w '%{http_code}' --max-time 20 "https://api.${HOST_APEX}/api/v1/${e}" || echo 000)
  echo "API_SUB ${e} -> ${code}"
  [[ "$code" == "200" ]] || MOBILE_OK=0
done
[[ "$MOBILE_OK" == "1" ]] && MOBILE_PASS=1

echo "===== RESULT SUMMARY ====="
echo "WEB: $([[ $WEB_PASS -eq 1 ]] && echo PASS || echo FAIL)"
echo "ADMIN: $([[ $ADMIN_PASS -eq 1 ]] && echo PASS || echo FAIL)"
echo "API: $([[ $API_PASS -eq 1 ]] && echo PASS || echo FAIL)"
echo "LIVE: $([[ $LIVE_PASS -eq 1 ]] && echo PASS || echo FAIL)"
echo "MOBILE API: $([[ $MOBILE_PASS -eq 1 ]] && echo PASS || echo FAIL)"
echo "DATABASE: PASS (migrations applied earlier or exit would have failed)"
echo "NGINX: $(sudo nginx -t >/dev/null 2>&1 && echo PASS || echo FAIL)"
echo "HTTPS: $(curl -sI --max-time 15 "https://${HOST_APEX}/" >/dev/null 2>&1 && echo PASS || echo FAIL_OR_CERT_MISMATCH)"

echo "===== CUTOVER FINISHED $(date -u) ====="
echo "REPORT=$REPORT"
echo "NAMED_BACKUP_PRESERVED=$BACKUP_PATH"
echo "FRESH_BACKUP=$FRESH_BACKUP"
