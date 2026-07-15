# Kurulum Rehberi (Local)

## 1. Depoyu klonlayın

```bash
git clone <repo-url>
cd artworld-platform
```

## 2. Veritabanı

```bash
sudo mysql
```

```sql
CREATE DATABASE artworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'artworld_user'@'localhost' IDENTIFIED BY 'güçlü_şifre';
GRANT ALL PRIVILEGES ON artworld.* TO 'artworld_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

```bash
mysql -u artworld_user -p artworld < database/migration.sql
mysql -u artworld_user -p artworld < database/seed.sql
```

> `migration.sql` içinde `CREATE DATABASE` ve `USE artworld` vardır. İsterseniz doğrudan `mysql -u root -p < database/migration.sql` çalıştırabilirsiniz.

## 3. Backend `.env`

```bash
cd backend
cp .env.example .env
nano .env
```

Kritik alanlar:

- `DB_PASSWORD`
- `APP_URL` / `MEDIA_URL`
- `APP_DEBUG=true` (yalnızca local)
- `FCM_ENABLED=false` (credential yoksa)

Upload klasör izinleri:

```bash
chmod -R 775 uploads
```

## 4. API’yi çalıştırın

```bash
cd backend/public
php -S 127.0.0.1:8080 router.php
```

Test:

```bash
curl http://127.0.0.1:8080/api/v1/home
curl http://127.0.0.1:8080/api/v1/news
curl http://127.0.0.1:8080/api/v1/live
```

## 5. Admin paneli

```bash
cd backend/admin
php -S 127.0.0.1:8081
```

Tarayıcı: `http://127.0.0.1:8081/login.php`

- E-posta: `admin@artworld.local`
- Şifre: `Admin123!`

## 6. Flutter

```bash
cd mobile
flutter pub get
```

`lib/core/config/app_config.dart`:

```dart
static const String apiBaseUrl = 'http://127.0.0.1:8080/api/v1';
```

Emülatörde Android için `10.0.2.2` kullanın:

```dart
static const String apiBaseUrl = 'http://10.0.2.2:8080/api/v1';
```

```bash
flutter run
flutter analyze
```

## 7. Firebase Cloud Messaging (opsiyonel)

1. Firebase Console’da proje oluşturun
2. Android/iOS uygulamalarını ekleyin
3. `google-services.json` / `GoogleService-Info.plist` ekleyin
4. Backend `.env`:

```
FCM_ENABLED=true
FCM_SERVER_KEY=your_server_key
```

5. Flutter tarafında FCM token otomatik `POST /api/v1/devices/register` ile kaydedilir
6. Credential yoksa uygulama çökmez; bildirim servisi yapılandırma hatası döner

## 8. Yaygın sorunlar

| Sorun | Çözüm |
|-------|--------|
| DB bağlantı hatası | `.env` kullanıcı/şifre ve MySQL servisini kontrol edin |
| Upload başarısız | `uploads/` yazma izni + `upload_max_filesize` |
| CORS | `CORS_ALLOWED_ORIGINS` |
| Emülatör API’ye ulaşamıyor | `10.0.2.2` veya gerçek cihaz IP |
| Türkçe karakter | Veritabanı `utf8mb4` |
