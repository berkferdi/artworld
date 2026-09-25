# Production checklist — Unified platform

## Domains (target)

- [ ] `https://artworldapi.com.tr/` → Web
- [ ] `https://artworldapi.com.tr/login` → Admin
- [ ] `https://artworldapi.com.tr/api/v1/` → API
- [ ] `https://media.artworldapi.com.tr/` → Media (optional)
- [ ] HTTP → HTTPS redirect
- [ ] Nginx config from `docs/nginx/artworldapi.com.tr.conf.md`
- [ ] `nginx -t` + reload
- [ ] SSL (Certbot)

## Environment

- [ ] `APP_ENV=production` `APP_DEBUG=false`
- [ ] `APP_URL=https://artworldapi.com.tr`
- [ ] `MEDIA_URL=https://artworldapi.com.tr` (or media subdomain)
- [ ] `APP_KEY` set (SecretBox)
- [ ] Web `API_BASE_URL=https://artworldapi.com.tr/api/v1`
- [ ] Flutter dart-define production URLs

## Database

- [ ] `database/migration.sql` (base)
- [ ] `migrations/002_web_unified_extensions.sql`
- [ ] `migrations/003_video_source_types.sql`
- [ ] No destructive DROP/TRUNCATE on prod data

## Smoke

```bash
curl -I https://artworldapi.com.tr/
curl -I https://artworldapi.com.tr/login
for e in home news videos programs live; do
  curl -s -o /dev/null -w "$e %{http_code}\n" https://artworldapi.com.tr/api/v1/$e
done
```

Until cutover, API smoke may use `https://api.artworldapi.com.tr/api/v1/…` (must stay 200).

## Admin

- [ ] Login / logout / CSRF
- [ ] News CRUD → appears on web + API
- [ ] Video YouTube
- [ ] Video File Server (local or remote)
- [ ] File Server settings + connection test
- [ ] Live URL → web home + mobile `/live`

## Security

- [ ] `.env` not public
- [ ] uploads deny PHP execution
- [ ] CORS tightened as needed (Flutter still works)
- [ ] Rate limit + login lockout

## File Server infrastructure

- [ ] Host online
- [ ] SFTP auth
- [ ] Public HTTPS media URL + Accept-Ranges
- [ ] Marked PENDING until verified
