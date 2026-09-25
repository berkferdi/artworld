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
[ ] Nginx cutover on production host (apex currently serves **admin** — `/` → 302 `/login.php`)
[ ] Apex HTTPS cert for `artworldapi.com.tr` (current leaf CN=`api.artworldapi.com.tr`)
[ ] File Server 193.35.155.55 SFTP online + media.artworldapi.com.tr HTTPS (HTTP:80 reachable; SSH:22 not)
[ ] Apply migrations 002/003 on production DB if not applied
[x] Safe cutover scripts: `scripts/deploy-on-server.sh`, `scripts/deploy-production.sh`

### PRODUCTION CUTOVER BLOCKER (2026-09-25)
Cloud Agent runtime=`managed` (hostname=`cursor`) — **not** on My Machines worker.
- Self-hosted workers connected: `art-VMware20-1` (`/var/www/artworld-platform`, workerId `e94559a0-8bed-5d10-83fb-d56962fcc6c2`)
- SSH `art@artworldapi.com.tr:22` TCP OK but **Permission denied (publickey)** — need `PROD_SSH_PRIVATE_KEY` + `PROD_SSH_USER`
- Task `machine.type=self_hosted_worker` not available from this client-executed path
- **Resume:** re-run agent with `worker=art-VMware20-1` **or** inject SSH secrets, then run `scripts/deploy-on-server.sh` / `scripts/deploy-production.sh`
- Named backup must remain: `/home/art/artworld-platform-backup-20260925-173634.tar.gz`

### API REGRESSION (api.artworldapi.com.tr)
[x] home/news/videos/programs/live → HTTP 200
