# API Referansı

Base URL: `{APP_URL}/api/v1`

Tüm cevaplar JSON ve UTF-8.

## Standart Başarı

```json
{
  "success": true,
  "message": "İşlem başarılı",
  "data": {},
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 100,
    "total_pages": 5
  }
}
```

## Standart Hata

```json
{
  "success": false,
  "message": "Bir hata oluştu",
  "errors": []
}
```

HTTP: `200`, `201`, `400`, `401`, `403`, `404`, `422`, `429`, `500`

---

## GET /home

Ana sayfa için optimize edilmiş tek endpoint.

**Response `data`:**

- `settings` — public uygulama ayarları
- `breaking_news[]`
- `banners[]`
- `featured_news[]` — özet alanlar (content yok)
- `latest_news[]`
- `featured_videos[]`
- `latest_videos[]`
- `programs[]`
- `live_stream` — aktif yayın veya `null`

---

## GET /news

Query:

| Param | Açıklama |
|-------|----------|
| `page` | Sayfa (default 1) |
| `per_page` | 1–50 (default 20) |
| `category` | Kategori slug |
| `featured` | `1` öne çıkanlar |
| `search` | Başlık/özet arama |
| `sort` | `latest` \| `oldest` \| `popular` |

---

## GET /news/featured

Öne çıkan haberler.

## GET /news/breaking

`is_breaking=1` yayınlanmış haberler.

## GET /news/{id-or-slug}

Haber detayı + `gallery` + `related`.

## POST /news/{id}/view

Body (JSON, opsiyonel):

```json
{ "device_uuid": "uuid-string" }
```

Aynı cihazdan 30 dk içinde tekrar sayılmaz.

---

## GET /categories

Aktif kategoriler.

## GET /categories/{slug}/news

Kategori + haber listesi. Pagination query destekler.

---

## GET /videos

Query: `page`, `per_page`, `category`, `featured`, `search`, `sort`

## GET /videos/{id-or-slug}

Video detayı + `related`.

## POST /videos/{id}/view

```json
{
  "device_uuid": "uuid",
  "watched_seconds": 30,
  "completed": 0
}
```

---

## GET /programs

## GET /programs/{id-or-slug}

Program + bölümler listesi.

## GET /programs/{id-or-slug}/episodes

Sayfalanmış bölümler.

## GET /episodes/{id-or-slug}

Bölüm detayı.

## POST /episodes/{id}/view

---

## GET /live

Aktif canlı yayın (`is_active=1` en güncel kayıt).

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Art World Canlı Yayın",
    "stream_url": "https://.../index.m3u8",
    "stream_type": "hls",
    "poster_image": "https://...",
    "is_active": true
  }
}
```

---

## GET /banners

Query: `position` (default `home_hero`)

## GET /settings/public

Mobil uygulama marka/ayar verileri.

## GET /search

Query: `q` (min 2 karakter)

```json
{
  "data": {
    "query": "antalya",
    "news": [],
    "videos": [],
    "programs": []
  }
}
```

---

## POST /devices/register

```json
{
  "fcm_token": "fcm-token",
  "platform": "android",
  "device_uuid": "optional-uuid",
  "app_version": "1.0.0"
}
```

`platform`: `android` | `ios`

**201** başarı.

**422** doğrulama hatası örneği:

```json
{
  "success": false,
  "message": "Doğrulama hatası",
  "errors": {
    "fcm_token": "FCM token zorunludur."
  }
}
```

---

## Medya URL’leri

Veritabanında relative path saklanır (`/videos/2026/07/file.mp4`).  
API cevaplarında `MEDIA_URL` ile birleştirilir.  
`http://` veya `https://` ile başlayan URL’ler olduğu gibi bırakılır.
