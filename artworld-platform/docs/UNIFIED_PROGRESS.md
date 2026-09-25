# Art World Unified Platform — Progress

## Final unified iteration (2026-09-25)

### COMPLETED (code)
[x] Live-first web homepage (no sidebar duplicate)
[x] Dual video sources + migration 003 + SmartVideoPlayer
[x] Admin File Server settings UI + connection test
[x] Encrypted secrets (SecretBox / APP_KEY)
[x] Dashboard File Server online/offline card
[x] Unified Nginx docs for artworldapi.com.tr
[x] ARCHITECTURE.md + VIDEO_SYSTEM.md + updated FILE_SERVER / PRODUCTION
[x] Env examples retargeted to artworldapi.com.tr

### INFRASTRUCTURE
[ ] Nginx cutover on production host (artworldapi.com.tr currently API-only → JSON 404 on `/`)
[ ] File Server 193.35.155.55 online + SFTP + media.artworldapi.com.tr HTTPS
[ ] Apply migrations 002/003 on production DB if not applied

### API REGRESSION (api.artworldapi.com.tr)
[x] home/news/videos/programs/live → HTTP 200
