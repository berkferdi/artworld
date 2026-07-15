# Art World Mobile Platform

Haber ve internet televizyonu platformu — Flutter mobil uygulama, PHP 8.2+ REST API ve Bootstrap 5.3 admin paneli.

## Özellikler

- Manşet slider, son dakika bandı, haber okuma
- Canlı yayın (HLS / YouTube / dış URL)
- Video galeri (MP4 / HLS)
- Programlar ve geçmiş bölümler
- Kategori filtreleme ve arama
- Push notification altyapısı (FCM)
- Marka ayarları admin panelinden yönetilebilir

## Gereksinimler

- PHP 8.2+ (pdo_mysql, mbstring, gd, curl, fileinfo)
- MySQL 8 / MariaDB 10.11+
- Nginx + PHP-FPM (production)
- Flutter 3.32+ / Dart 3.8+
- Android SDK / Xcode (mobil build)

## Klasör Yapısı

```
artworld-platform/
├── mobile/          # Flutter uygulaması
├── backend/
│   ├── api/         # API giriş alias
│   ├── admin/       # Yönetim paneli
│   ├── config/      # Bootstrap
│   ├── core/        # Framework çekirdeği
│   ├── controllers/ # API controllers
│   ├── models/
│   ├── middleware/
│   ├── services/
│   ├── public/      # Web root (API)
│   └── uploads/     # Medya dosyaları
├── database/
│   ├── migration.sql
│   └── seed.sql
└── docs/
```

## Backend Kurulumu

```bash
cd artworld-platform/backend
cp .env.example .env
# .env içindeki DB_* ve URL değerlerini düzenleyin
```

### MySQL

```bash
mysql -u root -p < ../database/migration.sql
mysql -u root -p artworld < ../database/seed.sql
```

`.env` örneği:

```
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8080
MEDIA_URL=http://127.0.0.1:8080
DB_HOST=localhost
DB_DATABASE=artworld
DB_USERNAME=artworld_user
DB_PASSWORD=YOUR_PASSWORD
```

### Yerel API sunucusu

```bash
cd backend/public
php -S 127.0.0.1:8080 router.php
```

API kontrol: `http://127.0.0.1:8080/api/v1/home`

### Admin paneli

Admin dosyaları `backend/admin/` altındadır. Geliştirmede ayrı port:

```bash
cd backend/admin
php -S 127.0.0.1:8081
```

**Varsayılan giriş (seed):**

| Alan | Değer |
|------|-------|
| E-posta | `admin@artworld.local` |
| Şifre | `Admin123!` |

> Production ortamında bu şifreyi mutlaka değiştirin.

## Flutter Kurulumu

```bash
cd artworld-platform/mobile
flutter pub get
```

API adresini `lib/core/config/app_config.dart` içinde güncelleyin:

```dart
static const String apiBaseUrl = 'https://api.example.com/api/v1';
static const String mediaBaseUrl = 'https://media.example.com';
```

```bash
flutter run
flutter analyze
flutter build apk --release
flutter build appbundle
```

**Paket adı:** `com.artworld.artworld_mobile`  
Değiştirmek için: `android/app/build.gradle.kts` içindeki `applicationId` ve iOS bundle identifier.

## Dokümantasyon

- [Kurulum](docs/INSTALLATION.md)
- [API Referansı](docs/API.md)
- [Production Deployment](docs/DEPLOYMENT.md)
- [İlerleme](docs/CURSOR_PROGRESS.md)

## Güvenlik Notları

- Şifreler `password_hash` / `password_verify` ile saklanır
- SQL: PDO prepared statements
- Admin: CSRF, session güvenliği, login rate limit
- Upload: MIME + uzantı + boyut kontrolü
- `.env` dosyasını repository’ye eklemeyin
