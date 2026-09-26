# Art World Platform — Architecture

## Goal

Single content system:

```text
Admin  →  MySQL  →  API  →  Web + Flutter
                     ↓
              Video sources
              /            \
        File Server      YouTube
```

## Production URLs (target)

| Role | URL |
|------|-----|
| Web | https://artworldapi.com.tr/ |
| Admin | https://artworldapi.com.tr/login → `/admin/` |
| API | https://artworldapi.com.tr/api/v1/ |
| Media | https://media.artworldapi.com.tr/ (optional) |
| File Server origin | 193.35.155.55 |

Legacy split hosts (`www.artworld.com.tr`, `api.artworldapi.com.tr`, `admin.artworldapi.com.tr`) remain documented for cutover; new deploys use the unified apex.

## Repository layout

```text
artworld-platform/
  backend/     PHP API + Admin
  web/         PHP SSR frontend (API client)
  mobile/      Flutter app
  database/    migration.sql + migrations/*.sql
  docs/
```

## Shared contracts (do not break)

```text
GET /api/v1/home
GET /api/v1/news
GET /api/v1/videos
GET /api/v1/programs
GET /api/v1/live
```

Additive fields only (e.g. `source_type`, `playback_url`, `/home/web`).

## Auth

- Admin: session + CSRF + login rate limit (`Auth`)
- API: public read endpoints + device register; rate limit middleware

## Media

1. Prefer `source_type=file_server` uploads via `FileServerService` (local / sftp / http)
2. Admin → Medya / File Server for credentials + connection test
3. Secrets encrypted with `SecretBox` (`APP_KEY`)
4. YouTube remains alternative source
