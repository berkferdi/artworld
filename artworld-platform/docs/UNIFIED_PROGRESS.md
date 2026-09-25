# Art World Unified Platform — Progress

## Live-first homepage + video source system (2026-09-25)

[x] Homepage: CANLI ART WORLD TV full-width top player (no sidebar duplicate)
[x] Live uses GET /api/v1/live (same as mobile)
[x] Autoplay muted + Sesi Aç overlay; HLS.js
[x] Ticker moved below live on homepage
[x] Video source_type migration 003 + FileServerService (local/sftp/http)
[x] Admin video CRUD: File Server / YouTube / External + source filter + delete file
[x] API: source_type + playback_url (video_url kept)
[x] Web video detail: HTML5 / HLS / YouTube embed by source
[x] Flutter SmartVideoPlayer (file_server via Chewie, youtube via launcher)
[x] File server probe documented (193.35.155.55 unreachable HTTP; use MEDIA_URL local mode)
[x] Mobile API regression: home/news/videos/programs/live → 200

## Prior phases 0–18
See previous UNIFIED_PROGRESS entries; web SSR + admin web modules remain in place.
