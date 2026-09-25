# File Server / Video Storage

## Status (2026-09-25)

| Check | Result |
|-------|--------|
| Code: FileServerService + Admin UI + encrypted secrets | **PASS** |
| Infrastructure: `193.35.155.55` HTTP/SFTP from cloud agent | **PENDING / BLOCKED** (TCP 80/443 hang, SSH timeout) |
| Local fallback (`FILE_SERVER_MODE=local` / Admin mode=local) | **PASS** |
| YouTube alternative source | **PASS** |

## Admin

**Sistem → File Server** (`backend/admin/file_server.php`)

- Enabled / Mode / Host / Port / User / Password (masked) / SSH key / Upload path / Public Media URL
- **Bağlantıyı Test Et** → TCP + SFTP auth/path or HTTP probe
- Dashboard card shows Online / Offline
- Secrets stored in `app_settings` via `SecretBox` (`APP_KEY`)

## Config resolution

1. Admin DB settings (`FileServerConfigService`)
2. Fallback `.env` `FILE_SERVER_*`

## Modes

| Mode | Behavior |
|------|----------|
| `local` | `backend/uploads/videos` + `MEDIA_URL` / Public Media URL |
| `sftp` | php-ssh2 upload; fallback to local on failure |
| `http` | multipart POST to upload URL; fallback to local |

## Public media target

```text
https://media.artworldapi.com.tr/
```

See `docs/nginx/artworldapi.com.tr.conf.md` for reverse-proxy example to `193.35.155.55`.

## Logging

`backend/uploads/file-server.log` — UPLOAD_* / FILE_SERVER_CONNECTION_FAILED (no secrets).
