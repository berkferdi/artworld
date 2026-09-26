# Video System

## Source types

| `source_type` | Playback | Admin input |
|---------------|----------|-------------|
| `file_server` | HTML5 / HLS / Flutter Chewie | Upload MP4 (etc.) |
| `youtube` | YouTube embed / Flutter launcher | YouTube URL |
| `external` | Direct URL | External URL |

Legacy `video_type` (`mp4`,`hls`,`youtube`,…) is retained for mobile BC.

## API shape (additive)

```json
{
  "id": 10,
  "title": "...",
  "video_url": "https://…",
  "playback_url": "https://…",
  "video_type": "mp4",
  "source_type": "file_server",
  "thumbnail": "https://…",
  "duration_seconds": 3600
}
```

`file_path` stays internal (admin/delete) — not required on public lists.

## Migration

```bash
mysql … < database/migrations/003_video_source_types.sql
```

Idempotent. Backfills `source_type` from `video_type` / URL.

## Players

- **Web**: `video-show.php` — YouTube iframe / `<video>` / HLS.js
- **Web live**: homepage `#home-live-video` + `/canli` from `GET /api/v1/live`
- **Flutter**: `SmartVideoPlayer` — file_server → `AppVideoPlayer`; youtube → external launch

## Upload flow

```text
Admin video form
  → UploadService (local validate MIME/size)
  → FileServerService.storeUploadedVideo()
       local | sftp | http
  → DB row (source_type=file_server)
  → API playback_url
  → Web + Mobile
```

Failed remote transfer keeps local copy and surfaces a note — never marks remote-only success without a path.

## File Server offline

YouTube continues to work. Local mode remains default until SFTP/HTTP probe is green in Admin → File Server.
