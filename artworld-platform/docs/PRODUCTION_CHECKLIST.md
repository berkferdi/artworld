# Production checklist — Unified Web + Mobile

## 1. Database
```bash
mysql -u artworld_user -p artworld < /var/www/artworld-platform/database/migrations/002_web_unified_extensions.sql
```

## 2. Deploy code
Deploy `backend/` (API + admin) and `web/` together. Mobile app needs no release for this phase.

## 3. Web env
```bash
cp /var/www/artworld-platform/web/.env.example /var/www/artworld-platform/web/.env
# Set API_BASE_URL to https://api.artworldapi.com.tr/api/v1
# Set CANONICAL_HOST to https://www.artworld.com.tr
```

## 4. Nginx + SSL
Follow `docs/nginx/artworld-web.conf.md`.

## 5. Smoke
```bash
curl -I https://www.artworld.com.tr/
for endpoint in home news videos programs live home/web; do
  curl -s -o /dev/null -w "$endpoint %{http_code}\n" "https://api.artworldapi.com.tr/api/v1/$endpoint"
done
```

Mobile endpoints must remain HTTP 200.
