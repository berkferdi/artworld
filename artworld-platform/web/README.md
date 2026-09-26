# Art World Web Frontend

PHP 8.3 SSR site that consumes the shared Art World REST API (same backend as mobile + admin).

## Run locally

```bash
cd web
cp .env.example .env   # if needed
php -S 127.0.0.1:8081 -t public public/router.php
```

Open http://127.0.0.1:8081/

Default `API_BASE_URL` points at production: `https://api.artworldapi.com.tr/api/v1`.

## Structure

```text
web/
  public/          # document root (index.php, router.php)
  app/             # Controllers, Core (ApiClient, Router, View, Cache), Helpers
  views/           # layouts, partials, pages
  assets/          # css, js, branding
  config/          # bootstrap
  storage/         # cache + logs
```

## Production

See `docs/nginx/artworld-web.conf.md`. Apply DB migration:

```bash
mysql -u ... artworld < database/migrations/002_web_unified_extensions.sql
```

Then deploy API/admin updates so `/api/v1/home/web`, pages, menus, galleries, etc. are available.
