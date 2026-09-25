# Art World Unified Platform — Progress

## PHASE 0 — Backup + Discovery
[x] Git branch: cursor/web-unified-platform-0ab6
[x] Plan copied to docs/ARTWORLD_WEB_MOBILE_UNIFIED_PLAN.md
[x] Project backup tarball created under /workspace/backups/
[x] Production mobile API regression: home/news/videos/programs/live → HTTP 200
[x] API JSON snapshots saved to docs/api-snapshots/
[ ] Local PHP/MySQL/Nginx (cloud agent: PHP CLI only; production Nginx/MySQL not on this VM)

## PHASE 1 — Web skeleton
[x] web/ folder structure
[x] public router + layout
[x] Smoke: php -l + HTTP 200 on `/`

## PHASE 2 — Nginx + Domain
[x] Docs/examples only in this cloud VM (`docs/nginx/artworld-web.conf.md`)
[ ] Production Nginx/SSL enablement (requires prod server access)

## PHASE 3 — Branding
[x] Theme CSS dark/cyan (`web/assets/css/theme.css`)
[x] Branding SVG placeholders + API logo support
[x] Header / footer / buttons / live badge

## PHASE 4 — API client
[x] Web `ApiClient` (timeout, retry, JSON validation, cache, logging)

## PHASE 5 — Ana Sayfa
[x] Home sections: ticker, header, menu, breaking, hero, latest, selected, videos, most read, programs, live, footer
[x] Falls back to `/api/v1/home` when `/home/web` unavailable

## PHASE 6 — Haber
[x] `/haber/{slug}`, `/kategori/{slug}`, `/arama` + SEO meta/OG/JSON-LD

## PHASE 7 — Video + Live + Programs
[x] `/video`, `/video/{slug}`, `/canli` (HLS.js), `/programlar`, `/program/{slug}`

## PHASE 8 — Gallery + Interviews + Authors
[x] DB migration `database/migrations/002_web_unified_extensions.sql`
[x] API models/controllers + admin CRUD
[x] Web pages (empty-state until migration applied on prod DB)

## PHASE 9 — Pages + Contact
[x] Corporate pages + contact form + API/admin pages module

## PHASE 10 — Ads
[x] Ads table + API + admin; web slots ready for later injection

## PHASE 11 — Menus
[x] Menus/menu_items + API + admin; web header uses API menus with fallback

## PHASE 12 — Services
[x] Services table + provider stub (market ticker inactive; no fake market data)

## PHASE 13 — Search + Archive
[x] Search + archive routes; API archive + extended search

## PHASE 14 — SEO + Sitemap
[x] robots.txt, sitemap.xml, sitemap-news.xml, article JSON-LD

## PHASE 15–18
[x] Basic cache (API client file cache), lazy images, gzip notes in Nginx doc
[ ] Full prod security audit on live server
[x] Local web integration smoke against prod API
[ ] Production migrate + Nginx reload on live host

## Notes
- Cloud agent workspace path: /workspace/artworld-platform (not /var/www)
- Do not break existing mobile API contract; use /api/v1/home/web for web-specific home
- Production API base: https://api.artworldapi.com.tr
- Web local: `php -S 127.0.0.1:8081 -t public public/router.php` from `web/`
