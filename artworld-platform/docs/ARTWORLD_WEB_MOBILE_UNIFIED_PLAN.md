# ART WORLD TV — Web + Mobile Unified Platform Implementation Plan

## 0. Proje hedefi

Mevcut Art World Mobile Platform backend'i korunacak ve aynı backend/API + aynı MySQL veritabanı üzerinden:

- Mobil uygulama
- Yeni web sitesi
- Admin paneli

tek içerik yönetim sistemi olarak çalışacaktır.

Ana hedef:

> **Tek admin panel → tek veritabanı → tek API → Web + Android/iOS**

Mevcut API alan adı:

- `https://api.artworldapi.com.tr`

Admin panel:

- `https://admin.artworldapi.com.tr`

Web sitesi hedefi:

- `https://www.artworld.com.tr`
- mümkünse `https://artworld.com.tr` → `https://www.artworld.com.tr` yönlendirmesi

---

# 1. ÇOK ÖNEMLİ: CURSOR ÇALIŞMA PROTOKOLÜ

Bu dosya Cursor Agent/Composer'a doğrudan verilecektir.

Cursor aşağıdaki kurallara kesinlikle uyacaktır:

### 1.1 Onay isteme

Kullanıcıdan:

- "Devam edeyim mi?"
- "Onaylıyor musunuz?"
- "Bir sonraki faza geçeyim mi?"
- "Şimdi bunu yapmamı ister misiniz?"

gibi onay istemeyecektir.

Planlanan fazları sırasıyla uygulayacaktır.

### 1.2 Faz mantığı

Her faz için:

1. Mevcut sistemi incele.
2. Gereken kod/veritabanı/dosya değişikliklerini yap.
3. Syntax/lint kontrolü yap.
4. İlgili servisleri kontrol et.
5. API endpoint testlerini çalıştır.
6. Web sayfasını HTTP üzerinden test et.
7. Gerekirse otomatik düzelt.
8. Test tekrar başarılı olunca sonraki faza geç.
9. Başarısız test varsa sonraki faza geçme.
10. Mevcut çalışan mobil API'lerini bozma.

### 1.3 Git

İlk iş:

```bash
cd /var/www/artworld-platform
git status
git branch --show-current
```

Git deposu varsa her büyük fazdan önce:

```bash
git add .
git commit -m "phase-X: description"
```

Git yoksa proje yedeği oluştur:

```bash
sudo tar -czf /root/artworld-platform-backup-$(date +%Y%m%d-%H%M%S).tar.gz /var/www/artworld-platform
```

### 1.4 Veritabanı güvenliği

Production veritabanında doğrudan rastgele `DROP TABLE`, `TRUNCATE` veya destructive migration çalıştırma.

Migration'lar:

- idempotent olmalı,
- `CREATE TABLE IF NOT EXISTS`,
- kontrollü `ALTER TABLE`,
- mümkünse migration dosyaları şeklinde tutulmalı.

Seed yalnızca ilk kurulum/test verisi için kullanılmalı.

### 1.5 Mevcut mobil API

Aşağıdaki endpoint'ler çalışır durumda kabul edilmiştir ve bozulmayacaktır:

```text
GET /api/v1/home
GET /api/v1/news
GET /api/v1/videos
GET /api/v1/programs
GET /api/v1/live
```

Her backend değişikliğinden sonra:

```bash
for endpoint in home news videos programs live; do
    echo "===== $endpoint ====="
    curl -s -o /dev/null -w "HTTP %{http_code} | %{time_total} saniye\n" \
    "https://api.artworldapi.com.tr/api/v1/$endpoint"
done
```

Beklenen:

```text
HTTP 200
```

---

# 2. MEVCUT SİSTEM ANALİZİ

## 2.1 Sunucu

Mevcut ortam:

```text
Ubuntu 24.04.4 LTS
Linux 6.17.0-40-generic
PHP 8.3.6
PHP-FPM 8.3
MySQL 8.0.46
Nginx
Certbot / Let's Encrypt
```

## 2.2 Backend

Proje:

```text
/var/www/artworld-platform/backend
```

Mevcut yapı:

```text
admin/
api/
config/
controllers/
core/
middleware/
models/
public/
services/
uploads/
```

Mevcut admin modülleri:

```text
admins
banners
breaking
categories
episodes
live
news
notifications
programs
settings
system
videos
```

Mevcut API controller'lar:

```text
BannerController
CategoryController
DeviceController
EpisodeController
HomeController
LiveController
NewsController
ProgramController
SearchController
SettingsController
VideoController
```

## 2.3 Mevcut veritabanı

Mevcut tablolar:

```text
admins
api_rate_limits
app_settings
banners
breaking_news
categories
device_tokens
live_streams
login_attempts
news
news_images
news_views
notifications
program_episodes
programs
video_views
videos
```

Toplam:

```text
17 tablo
```

## 2.4 Mevcut .env

Production ortamında:

```env
APP_ENV=local
APP_DEBUG=false

APP_URL=https://api.artworldapi.com.tr
MEDIA_URL=https://api.artworldapi.com.tr

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=artworld
DB_USERNAME=artworld_app
DB_PASSWORD=<production secret>
DB_CHARSET=utf8mb4

TIMEZONE=UTC
```

`APP_DEBUG=false` korunmalıdır.

---

# 3. ESKİ ARTWORLD.COM.TR SİTESİ ANALİZİ

Kaynak site:

```text
https://www.artworld.com.tr/
```

Mevcut site haber portalı + internet televizyonu yapısındadır.

Analiz edilen ana yapı:

## Üst piyasa/ticker alanı

Mevcut sitede:

- BIST
- Altın
- Dolar
- Euro

gösterilmektedir.

Bu bölüm yeni sitede daha modern bir ticker olarak tasarlanacaktır.

## Ana kategori navigasyonu

Mevcut sitede:

- ANTALYA
- GÜNDEM
- SİYASET
- ASAYİŞ
- EKONOMİ
- DÜNYA
- EĞİTİM
- SAĞLIK
- TURİZM
- SPOR
- KÜLTÜR SANAT
- TÜRKİYE
- CANLI YAYIN / ART WORLD TV
- RESMİ REKLAMLAR
- KÜNYEMİZ

gibi kategoriler bulunuyor.

Yeni sistemde kategori listesi hard-code edilmeyecek.

`categories` tablosundan dinamik gelecektir.

## Ana menü

Mevcut sitede:

- Üye İşlemi
- Canlı Yayın
- Foto Galeri
- Video Galeri
- Yazarlar
- Röportajlar
- Seçim Özel
- Yerel Haber
- Bize Ulaşın
- Arşivler
- Sitede Ara

gibi bağlantılar bulunuyor.

Yeni sitede bunlar yönetilebilir navigasyon haline getirilecek.

## Servisler

Mevcut sitede:

- Hava Durumu
- Yol ve Trafik
- Namaz Vakitleri
- Piyasalar
- Puan Durumu
- Nöbetçi Eczaneler
- Astroloji
- Sinemalar
- Rüya Tabirleri
- Gazete Manşetleri
- Tarihte Bugün
- Günün Sözü

gibi servisler bulunuyor.

Bu servislerin tamamı ilk aşamada uydurma veri ile yapılmayacaktır.

Önce modül altyapısı hazırlanacak.

Dış veri gerektiren servisler için ayrı provider/adapter yapısı kullanılacak.

---

# 4. YENİ TASARIM YÖNÜ

Eski sitenin bilgi mimarisi korunacak fakat görsel dil yenilenecek.

Kullanılacak marka yaklaşımı:

- Art World logosu
- koyu lacivert/siyah zemin
- elektrik mavi / cyan vurgular
- beyaz
- gerektiğinde gümüş tonları
- düşük miktarda accent renk
- yüksek kontrast
- modern haber portalı görünümü
- TV/live yayın kimliği

Logo kaynakları:

- kare Art World logosu
- yatay "ART WORLD / ART WORLD TV" logosu

Logo dosyaları `public/assets/images/branding/` altında standartlaştırılacak.

---

# 5. WEB TASARIMI

Yeni web sitesi responsive olacaktır.

Desteklenecek:

- Desktop
- Laptop
- Tablet
- Mobile

## Ana sayfa önerilen yapı

```text
┌─────────────────────────────────────────────┐
│ MARKET TICKER                               │
│ BIST | ALTIN | DOLAR | EURO                │
├─────────────────────────────────────────────┤
│ LOGO                 CANLI YAYIN   ARA     │
├─────────────────────────────────────────────┤
│ ANA MENÜ                                     │
│ ANTALYA GÜNDEM SİYASET ...                  │
├─────────────────────────────────────────────┤
│ SON DAKİKA / BREAKING NEWS                  │
├─────────────────────────────────────────────┤
│                                             │
│ HERO HABER           │ CANLI ART WORLD TV   │
│ büyük görsel         │ video/player        │
│ başlık               │ program/yayın       │
│                                             │
├─────────────────────────────────────────────┤
│ SON HABERLER                                  │
│ haber kartları                               │
├─────────────────────────────────────────────┤
│ KATEGORİLERE GÖRE HABERLER                  │
├─────────────────────────────────────────────┤
│ VİDEOLAR                                     │
├─────────────────────────────────────────────┤
│ ÇOK OKUNANLAR                                │
├─────────────────────────────────────────────┤
│ SİZİN İÇİN SEÇTİKLERİMİZ                    │
├─────────────────────────────────────────────┤
│ ART WORLD TV / PROGRAMLAR                   │
├─────────────────────────────────────────────┤
│ SERVİSLER                                    │
├─────────────────────────────────────────────┤
│ FOOTER                                       │
└─────────────────────────────────────────────┘
```

---

# 6. TEK PANEL MANTIĞI

En önemli mimari kural:

```text
                 ┌──────────────┐
                 │ Admin Panel  │
                 └──────┬───────┘
                        │
                        ▼
                 ┌──────────────┐
                 │    MySQL     │
                 └──────┬───────┘
                        │
                 ┌──────▼───────┐
                 │ REST API     │
                 └───┬──────┬───┘
                     │      │
              ┌──────▼─┐  ┌─▼──────┐
              │  Web   │  │ Mobile │
              └────────┘  └────────┘
```

Admin panelde haber oluşturulduğunda:

```text
Admin → News
      ↓
MySQL
      ↓
API
      ↓
Web
      ↓
Mobile
```

Aynı haber iki kere girilmeyecek.

---

# 7. VERİTABANI GENİŞLETME PLANI

Mevcut tablolar korunacak.

Gerekli yeni tablolar:

## pages

Kurumsal statik sayfalar:

```text
id
title
slug
content
meta_title
meta_description
status
created_at
updated_at
```

Örnek:

```text
Hakkımızda
Yayın İlkeleri
Kullanım Şartları
Gizlilik Politikası
KVKK / Veri Politikası
Kullanıcı Sözleşmesi
İrtibat Bilgileri
```

## authors

```text
id
name
slug
bio
photo
status
created_at
updated_at
```

## galleries

```text
id
title
slug
description
cover_image
status
created_at
updated_at
```

## gallery_images

```text
id
gallery_id
image
caption
sort_order
created_at
```

## interviews

```text
id
title
slug
summary
content
cover_image
author
status
published_at
created_at
updated_at
```

## ads

```text
id
name
position
type
content
image
link
start_at
end_at
status
sort_order
created_at
updated_at
```

## menus

```text
id
title
location
sort_order
status
```

## menu_items

```text
id
menu_id
parent_id
title
url
target
sort_order
status
```

## services

```text
id
name
slug
type
config
status
sort_order
```

## site_settings

Genişletilmiş web ayarları:

```text
site_name
site_description
logo
logo_mobile
favicon
primary_color
secondary_color
accent_color
footer_text
contact_phone
contact_email
address
facebook_url
instagram_url
youtube_url
x_url
whatsapp_url
```

## seo_settings

```text
page_type
page_id
meta_title
meta_description
canonical_url
robots
og_title
og_description
og_image
```

---

# 8. API GENİŞLETME

Mevcut endpoint'ler korunacak.

Yeni endpoint grupları:

```text
GET /api/v1/pages
GET /api/v1/pages/{slug}

GET /api/v1/authors
GET /api/v1/authors/{slug}

GET /api/v1/galleries
GET /api/v1/galleries/{slug}

GET /api/v1/interviews
GET /api/v1/interviews/{slug}

GET /api/v1/ads
GET /api/v1/menus
GET /api/v1/services

GET /api/v1/search?q=
GET /api/v1/archive

GET /api/v1/site-map
GET /api/v1/settings
```

Arama:

```text
GET /api/v1/search?q=antalya
```

Sonuçlar:

```json
{
  "news": [],
  "videos": [],
  "galleries": [],
  "interviews": []
}
```

---

# 9. HOME API GENİŞLETME

`HomeController` yeni web ana sayfasını besleyecek şekilde genişletilecek.

Önerilen response:

```json
{
  "success": true,
  "data": {
    "breaking": [],
    "hero": [],
    "latest_news": [],
    "featured_news": [],
    "most_read": [],
    "selected": [],
    "videos": [],
    "programs": [],
    "live": {},
    "banners": [],
    "categories": []
  }
}
```

Mobil uygulamanın mevcut response yapısı bozulmayacak.

Gerekirse:

```text
/api/v1/home
/api/v1/home/web
```

ayrımı kullanılabilir.

---

# 10. WEB PROJESİ KLASÖR YAPISI

Backend ile frontend ayrılacak.

Önerilen:

```text
/var/www/artworld-platform/
├── backend/
├── web/
│   ├── public/
│   ├── app/
│   ├── config/
│   ├── views/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   │   └── branding/
│   ├── storage/
│   └── .env
├── database/
└── docs/
```

Web frontend mevcut backend'in kullandığı PHP 8.3 ortamıyla uyumlu olacaktır.

Framework eklemek zorunlu değildir.

İlk hedef:

- hızlı,
- güvenli,
- SEO uyumlu,
- düşük kaynak tüketimli,
- API tabanlı PHP frontend.

---

# 11. WEB ROUTE PLANI

```text
/
 /haber/{slug}
 /kategori/{slug}
 /video
 /video/{slug}
 /canli
 /programlar
 /program/{slug}
 /foto-galeri
 /galeri/{slug}
 /yazarlar
 /yazar/{slug}
 /roportajlar
 /roportaj/{slug}
 /arsiv
 /arama?q=
 /hakkimizda
 /yayin-ilkeleri
 /kullanim-sartlari
 /gizlilik-politikasi
 /kvkk
 /iletisim
 /kunye
```

---

# 12. HABER DETAY SAYFASI

Haber detayında:

- kategori
- başlık
- özet
- kapak görseli
- yayın tarihi
- yazar
- haber metni
- galeri
- paylaşım butonları
- ilgili haberler
- çok okunanlar
- reklam alanları

olacak.

SEO:

```html
<title>
<meta name="description">
<link rel="canonical">
<meta property="og:title">
<meta property="og:description">
<meta property="og:image">
```

JSON-LD:

```text
NewsArticle
BreadcrumbList
Organization
```

uygulanacak.

---

# 13. KATEGORİ SAYFASI

Örnek:

```text
/kategori/antalya
/kategori/gundem
/kategori/spor
```

Yapı:

```text
Kategori başlığı
↓
Öne çıkan haber
↓
Haber grid
↓
Pagination
↓
Çok okunanlar
↓
Reklam
```

Pagination API ile yapılacak.

---

# 14. CANLI YAYIN

Mevcut:

```text
live_streams
LiveController
```

kullanılacak.

Web'de:

```text
ART WORLD TV
[ LIVE PLAYER ]
```

gösterilecek.

Player formatı backend'deki mevcut stream bilgisine göre uygulanacak.

HLS varsa:

```text
.m3u8
```

native/HLS.js desteği değerlendirilecek.

Canlı yayın yoksa:

```text
"Şu anda canlı yayın bulunmuyor."
```

gösterilecek.

---

# 15. VIDEO

Mevcut:

```text
videos
VideoController
```

kullanılacak.

Video listesi:

```text
/video
```

Video detay:

```text
/video/{slug}
```

Ana sayfada:

```text
VİDEOLAR
```

bölümü oluşturulacak.

---

# 16. PROGRAMLAR

Mevcut:

```text
programs
program_episodes
ProgramController
EpisodeController
```

kullanılacak.

Web:

```text
/programlar
/program/{slug}
```

Program detayında bölümler listelenecek.

---

# 17. FOTO GALERİ

Admin paneline:

```text
Foto Galerileri
Fotoğraf Ekle
Fotoğraf Sırala
Kapak Fotoğrafı
```

eklenecek.

Frontend:

```text
/foto-galeri
/galeri/{slug}
```

---

# 18. REKLAM YÖNETİMİ

Tek panelden:

```text
Üst banner
Ana sayfa banner
Sidebar
Haber içi
Video önü
Mobil banner
Footer
```

yönetilebilir olacak.

Önemli:

Mobil ve Web aynı reklam kaynağını kullanabilir.

Ancak responsive format gerekiyorsa:

```text
desktop_image
mobile_image
```

alanları kullanılacak.

---

# 19. MENÜ YÖNETİMİ

Admin panel:

```text
Menüler
├── Ana Menü
├── Üst Menü
├── Footer Menü
└── Mobil Menü
```

Menü item:

```text
Başlık
URL
Parent
Sıra
Aktif/Pasif
Yeni sekmede aç
```

Böylece kategori eklenince PHP kodu değiştirmek gerekmeyecek.

---

# 20. SEO

Aşağıdakiler hazırlanacak:

```text
/robots.txt
/sitemap.xml
/sitemap-news.xml
```

Canonical URL.

OpenGraph.

Twitter/X card.

NewsArticle JSON-LD.

Breadcrumb JSON-LD.

Organization JSON-LD.

Sayfalama canonical kuralları.

Görsellerde:

```html
alt
width
height
loading="lazy"
```

kullanılacak.

---

# 21. PERFORMANS

Web sitesi:

- CSS minimize
- JS minimize
- WebP/AVIF mümkünse
- lazy loading
- responsive images
- browser cache
- gzip/brotli
- Nginx cache uygun alanlarda
- API response cache

kullanacak.

Özellikle ana sayfada gereksiz yüzlerce API çağrısı yapılmayacak.

Tercih:

```text
1 adet Home API
+
gerekli detay endpointleri
```

---

# 22. GÜVENLİK

Production:

```env
APP_DEBUG=false
```

kalacak.

`.env` web root altında public olmayacak.

Upload:

- MIME doğrulama
- extension doğrulama
- dosya boyutu
- random filename
- PHP execution engelleme
- image validation

yapılacak.

Admin:

- CSRF
- session security
- rate limit
- brute-force protection
- secure cookies
- HTTPS

kontrol edilecek.

---

# 23. NGINX

Web için yeni server block oluştur:

```text
www.artworld.com.tr
artworld.com.tr
```

Document root:

```text
/var/www/artworld-platform/web/public
```

API:

```text
api.artworldapi.com.tr
```

Admin:

```text
admin.artworldapi.com.tr
```

birbirinden ayrı kalacak.

Sonra:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

test edilmeden reload yapılmayacak.

---

# 24. SSL

Web domain için:

```bash
sudo certbot --nginx \
-d artworld.com.tr \
-d www.artworld.com.tr
```

Sonra:

```bash
sudo certbot renew --dry-run
```

çalıştırılacak.

---

# 25. DNS

DNS tarafında:

```text
artworld.com.tr        A → SERVER_IP
www.artworld.com.tr    A → SERVER_IP
api.artworldapi.com.tr A → SERVER_IP
admin.artworldapi.com.tr A → SERVER_IP
```

kontrol edilecek.

DNS mevcut değilse Cursor DNS sağlayıcısına erişemediğini raporlayacak ve gerekli kayıtları kullanıcıya net olarak gösterecek; DNS bilgisi uydurmayacak.

---

# 26. CACHE

API cache eklenirse içerik değiştiğinde otomatik invalidation yapılacak.

Örneğin haber oluşturulduğunda:

```text
home cache
news cache
category cache
sitemap cache
```

invalidate edilecek.

---

# 27. ADMIN PANEL GELİŞTİRME

Mevcut admin panel korunacak.

Yeni menüler:

```text
İÇERİK
├── Haberler
├── Kategoriler
├── Son Dakika
├── Videolar
├── Canlı Yayın
├── Programlar
├── Bölümler
├── Foto Galerileri
├── Röportajlar
└── Yazarlar

WEB
├── Menüler
├── Sayfalar
├── Bannerlar
├── Reklamlar
├── SEO
└── Site Ayarları

SERVİSLER
├── Piyasalar
├── Hava Durumu
├── Trafik
├── Namaz
├── Eczaneler
├── Sinemalar
└── Diğer

SİSTEM
├── Adminler
├── Bildirimler
├── Sistem
└── Loglar
```

---

# 28. DASHBOARD

Admin dashboard:

```text
Bugünkü Haberler
Son Dakika
Toplam Haber
Toplam Video
Toplam Galeri
Toplam Program
Canlı Yayın Durumu
Son 24 Saat Görüntülenme
En Çok Okunan Haberler
Son Eklenen İçerikler
```

gösterecek.

---

# 29. MOBİL UYGULAMA UYUMU

Mobil uygulama mevcut API üzerinden çalışmaya devam edecek.

Web için yapılan değişiklikler mobil API'yi bozmayacak.

Yeni alanlar mümkünse geriye dönük uyumlu eklenecek.

Örneğin:

```json
{
  "id": 6,
  "title": "alanya son dakika",
  "slug": "alanya-son-dakika"
}
```

gibi mevcut alanlar korunacak.

---

# 30. MEDYA YÖNETİMİ

Mevcut upload:

```text
backend/uploads/
```

korunabilir.

Ancak API response'larında absolute URL kullanılacak:

```text
https://api.artworldapi.com.tr/images/...
```

Web doğrudan filesystem'e erişmeyecek.

---

# 31. TEST SİSTEMİ

Her fazın sonunda Cursor otomatik test çalıştıracak.

## PHP syntax

```bash
find backend -name "*.php" -print0 | \
xargs -0 -n1 php -l
```

Web için:

```bash
find web -name "*.php" -print0 | \
xargs -0 -n1 php -l
```

## Nginx

```bash
sudo nginx -t
```

## PHP-FPM

```bash
sudo systemctl is-active php8.3-fpm
```

## MySQL

```bash
sudo systemctl is-active mysql
```

## API

```bash
curl -I https://api.artworldapi.com.tr/api/v1/home
curl -I https://api.artworldapi.com.tr/api/v1/news
curl -I https://api.artworldapi.com.tr/api/v1/videos
curl -I https://api.artworldapi.com.tr/api/v1/programs
curl -I https://api.artworldapi.com.tr/api/v1/live
```

## Web

```bash
curl -I https://www.artworld.com.tr/
curl -I https://artworld.com.tr/
```

Beklenen:

```text
200
```

veya bilinçli redirect:

```text
301/302
```

---

# 32. API JSON TEST

```bash
curl -fsS https://api.artworldapi.com.tr/api/v1/home | python3 -m json.tool
```

Başarılı JSON dönmeli.

Aynı şekilde:

```bash
curl -fsS https://api.artworldapi.com.tr/api/v1/news | python3 -m json.tool
curl -fsS https://api.artworldapi.com.tr/api/v1/videos | python3 -m json.tool
curl -fsS https://api.artworldapi.com.tr/api/v1/programs | python3 -m json.tool
curl -fsS https://api.artworldapi.com.tr/api/v1/live | python3 -m json.tool
```

---

# 33. GERÇEK VERİ TESTİ

Admin panelden:

```text
alanya son dakika
```

gibi test haberi oluştur.

API:

```bash
curl -s https://api.artworldapi.com.tr/api/v1/news
```

içerisinde haber bulunmalı.

Web:

```text
https://www.artworld.com.tr/
```

üzerinde görünmeli.

Mobil uygulamada da aynı haber görünmeli.

Bu test üçlü olarak başarılı olmadan faz tamamlanmış sayılmayacak:

```text
ADMIN → API → WEB
ADMIN → API → MOBILE
```

---

# 34. KIRILMA TESTİ

Mevcut test haberini değiştirme.

Yeni bir test kaydı oluştur.

Sonra:

1. Admin'den yayınla.
2. API'den kontrol et.
3. Web'den kontrol et.
4. Mobil API'den kontrol et.
5. Görsel yükle.
6. Görsel URL'sini kontrol et.
7. Haberi pasif yap.
8. Web'de listeden kalktığını kontrol et.
9. API'de durumunu kontrol et.

---

# 35. RESPONSIVE TEST

En az:

```text
360x800
390x844
768x1024
1024x768
1366x768
1920x1080
```

kontrol edilecek.

Kontrol listesi:

- Menü
- Logo
- Breaking news
- Hero
- Haber kartları
- Video
- Live player
- Sidebar
- Footer
- reklam
- görseller
- font taşmaları
- yatay scroll

---

# 36. SEO TEST

Kontrol:

```bash
curl -s https://www.artworld.com.tr/robots.txt
curl -s https://www.artworld.com.tr/sitemap.xml
```

Haber:

```bash
curl -s https://www.artworld.com.tr/haber/alanya-son-dakika
```

kontrol edilecek.

HTML içinde:

```text
<title>
description
canonical
og:title
og:description
og:image
application/ld+json
```

aranacak.

---

# 37. FAZLAR

## PHASE 0 — Backup + Discovery

Yap:

- Git kontrolü
- backup
- mevcut Nginx config analizi
- mevcut API analizi
- mevcut admin analizi
- mevcut DB schema analizi
- mevcut mobile API response snapshot

Test:

```bash
nginx -t
systemctl is-active nginx
systemctl is-active php8.3-fpm
systemctl is-active mysql
```

Başarısızsa sonraki faza geçme.

---

## PHASE 1 — Web skeleton

Oluştur:

```text
web/
web/public/
web/app/
web/views/
web/assets/
```

Basit responsive layout oluştur.

Test:

```bash
php -l
curl -I
```

---

## PHASE 2 — Nginx + Domain

Kur:

```text
www.artworld.com.tr
artworld.com.tr
```

SSL.

Test:

```bash
nginx -t
curl -I https://www.artworld.com.tr/
```

---

## PHASE 3 — Branding

Eklenmeli:

- Art World logo
- Art World TV logo
- favicon
- dark/cyan/white theme
- typography
- buttons
- cards
- header
- footer

Logo dosyalarının gerçek dosya adlarını otomatik tespit et.

---

## PHASE 4 — API client

Web frontend API client oluştur.

Tekrarlı curl/fetch kodlarını merkezi sınıfa taşı.

Örneğin:

```text
ApiClient
```

özellikleri:

- timeout
- HTTP error handling
- JSON validation
- retry
- logging
- cache desteği

---

## PHASE 5 — Ana Sayfa

Eski sitenin içerik yapısını yeni tasarıma aktar:

- ticker
- header
- menu
- breaking
- hero
- latest
- featured
- videos
- most read
- selected
- programs
- live
- footer

---

## PHASE 6 — Haber

Yap:

```text
/haber/{slug}
/kategori/{slug}
/arama
```

SEO ekle.

---

## PHASE 7 — Video + Live + Programs

Yap:

```text
/video
/video/{slug}
/canli
/programlar
/program/{slug}
```

---

## PHASE 8 — Gallery + Interviews + Authors

DB + API + Admin + Web.

---

## PHASE 9 — Pages + Contact + Corporate

Statik sayfaları admin panelden yönetilebilir yap.

---

## PHASE 10 — Ads + Banners

Web reklam pozisyonlarını admin panelden yönet.

---

## PHASE 11 — Menus

Menü yönetim sistemini oluştur.

---

## PHASE 12 — Services

Önce provider altyapısı.

Sonra gerçek servis entegrasyonları.

Fake/random veri kullanma.

---

## PHASE 13 — Search + Archive

Arama:

```text
haber
video
galeri
röportaj
```

Archive:

```text
tarih
kategori
```

---

## PHASE 14 — SEO + Sitemap

Oluştur:

```text
robots.txt
sitemap.xml
sitemap-news.xml
```

JSON-LD.

---

## PHASE 15 — Performance

- caching
- compression
- lazy loading
- WebP/AVIF
- CSS/JS optimization
- Nginx cache

---

## PHASE 16 — Security Audit

Kontrol:

- SQL injection
- XSS
- CSRF
- upload
- session
- authorization
- rate limit
- `.env`
- directory listing
- PHP execution in upload folders

---

## PHASE 17 — Full Integration Test

Test:

```text
Admin
 ↓
MySQL
 ↓
API
 ├── Web
 └── Mobile
```

Gerçek test içerikleriyle.

---

## PHASE 18 — Production Final

Kontrol:

```bash
sudo nginx -t
sudo systemctl reload nginx

sudo systemctl status nginx --no-pager
sudo systemctl status php8.3-fpm --no-pager
sudo systemctl status mysql --no-pager

sudo certbot renew --dry-run
```

Sonra:

```bash
curl -I https://www.artworld.com.tr/
curl -I https://api.artworldapi.com.tr/api/v1/home
curl -I https://admin.artworldapi.com.tr/
```

---

# 38. CURSOR'UN HER FAZ SONU RAPOR FORMATI

Cursor her fazın sonunda kısa olarak şunu yazacak:

```text
PHASE X TAMAMLANDI

Yapılanlar:
- ...
- ...
- ...

Değişen dosyalar:
- ...
- ...

Testler:
[PASS] PHP syntax
[PASS] MySQL
[PASS] API
[PASS] Nginx
[PASS] HTTP
[PASS] Responsive

Sonuç:
PASS

Sonraki faz:
PHASE X+1
```

Bir test FAIL olursa:

```text
PHASE X BLOKLANDI
```

yazacak, hatayı kendisi çözmeye çalışacak ve tekrar test edecektir.

---

# 39. KRİTİK KURAL: MOBİLİ BOZMA

Web geliştirilirken aşağıdaki API'ler regresyon testine girecek:

```text
/home
/news
/videos
/programs
/live
```

Her değişiklikten sonra 5 endpoint test edilecek.

Örneğin:

```bash
for endpoint in home news videos programs live; do
    code=$(curl -s -o /tmp/artworld-${endpoint}.json -w "%{http_code}" \
      "https://api.artworldapi.com.tr/api/v1/${endpoint}")

    echo "${endpoint}: HTTP ${code}"

    test "$code" = "200" || exit 1

    python3 -m json.tool \
      "/tmp/artworld-${endpoint}.json" >/dev/null || exit 1
done
```

Hepsi PASS olmadan sonraki faza geçme.

---

# 40. SON KABUL KRİTERLERİ

Proje ancak aşağıdakilerin tamamı çalışıyorsa tamamlanmış kabul edilir:

### Admin

- Haber ekleme
- Haber düzenleme
- Haber silme/pasifleştirme
- Kategori
- Son dakika
- Video
- Program
- Bölüm
- Canlı yayın
- Galeri
- Yazar
- Röportaj
- Sayfa
- Menü
- Banner
- Reklam
- SEO
- Site ayarları

### Web

- Ana sayfa
- Haber
- Kategori
- Video
- Canlı
- Program
- Galeri
- Yazar
- Röportaj
- Arama
- Arşiv
- Kurumsal sayfalar
- İletişim
- SEO
- Sitemap
- Responsive tasarım

### Mobile

Mevcut çalışan özellikler bozulmayacak.

### Tek panel

Bir haber sadece admin panelden girilecek.

Aynı içerik:

```text
Web + Mobile
```

üzerinde otomatik görünecek.

---

# 41. SON NOT

Bu proje eski web sitesinin birebir eski tasarımını kopyalamak yerine:

> **Eski sitenin içerik mimarisi + yeni Art World TV marka kimliği + mevcut mobil API + tek admin panel**

birleşimi olarak yapılacaktır.

Özellikle eski sitenin:

- haber portalı yapısı,
- kategori yapısı,
- canlı yayın,
- video,
- program,
- galeri,
- servisler,
- çok okunanlar,
- seçilen içerikler,
- son eklenenler,
- kurumsal sayfalar

korunacaktır.

Görsel tarafta ise daha temiz, daha hızlı, modern ve Art World logosundaki mavi/cyan/lacivert kimliğe uygun tasarım kullanılacaktır.

---

# 42. WEB KABUL TESTİ

Finalde Cursor aşağıdaki senaryoyu uçtan uca çalıştıracak:

### Senaryo

1. Admin'e giriş yap.
2. Yeni kategori oluştur.
3. Yeni haber oluştur.
4. Görsel yükle.
5. Haberi yayınla.
6. API'den haberi doğrula.
7. Web ana sayfada doğrula.
8. Kategori sayfasında doğrula.
9. Haber detayında doğrula.
10. Mobil API'de doğrula.
11. Video oluştur.
12. Web video sayfasında doğrula.
13. Program oluştur.
14. Web program sayfasında doğrula.
15. Canlı yayın kaynağını kontrol et.
16. Menü değiştir.
17. Web menüsünü doğrula.
18. Banner ekle.
19. Web'de bannerı doğrula.
20. SEO meta alanlarını doğrula.
21. Sitemap'i doğrula.
22. SSL'i doğrula.
23. Mobil API regression testini çalıştır.

Tüm testler PASS ise:

```text
ART WORLD WEB + MOBILE UNIFIED PLATFORM
PRODUCTION READY
```

raporu oluştur.

