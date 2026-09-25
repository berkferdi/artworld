# File Server / Video Storage

## Discovery (2026-09-25)

| Host | Port | Result |
|------|------|--------|
| `193.35.155.55` | 80 | TCP connect OK, **no HTTP response** (timeout) |
| `193.35.155.55` | 443 | TCP connect OK, **TLS handshake timeout** |
| `193.35.155.55` | 22 | **Not reachable** from cloud agent |
| `193.35.155.149` / `api.artworldapi.com.tr` | 80/443 | **OK** — Nginx, `Accept-Ranges: bytes` on media |

Conclusion: direct File Server IP is not usable from this environment yet. Public media currently serves via API host (`MEDIA_URL`).

## Config (`.env`)

```env
FILE_SERVER_ENABLED=true
FILE_SERVER_MODE=local          # local | sftp | http
FILE_SERVER_HOST=193.35.155.55
FILE_SERVER_PORT=22
FILE_SERVER_USER=
FILE_SERVER_PASSWORD=
FILE_SERVER_SSH_KEY=
FILE_SERVER_BASE_PATH=/var/www/artworld
FILE_SERVER_PUBLIC_BASE_URL=    # e.g. https://media.artworld.com.tr
FILE_SERVER_UPLOAD_URL=
FILE_SERVER_UPLOAD_TOKEN=
```

- **local** (default): `UploadService` → `backend/uploads/videos/...`, playback via `MEDIA_URL`
- **sftp**: requires `php-ssh2` + credentials; on failure keeps local copy
- **http**: multipart POST to upload endpoint; on failure keeps local copy

## API fields (backward compatible)

Existing: `video_url`, `video_type`

Added: `source_type` (`file_server`|`youtube`|`external`), `playback_url` (public URL; same as resolved `video_url`)

`file_path` is stored in DB for admin/delete — **not** exposed on public list API.

## Migration

```bash
mysql ... < database/migrations/003_video_source_types.sql
```

## Range / seek

API media host already returns `Accept-Ranges: bytes`. Prefer HTTPS public URL (not bare HTTP IP) to avoid mixed-content on `https://www.artworld.com.tr`.
