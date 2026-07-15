# Production Deployment

Ubuntu + Nginx + PHP-FPM + MySQL + Let’s Encrypt

Örnek domainler:

- `https://admin.example.com` — Admin paneli
- `https://api.example.com` — REST API
- `https://media.example.com` — Medya / HLS

## 1. Sunucu paketleri

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-gd \
  php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip unzip ffmpeg certbot python3-certbot-nginx
```

## 2. PHP upload limitleri (büyük video için)

`/etc/php/8.3/fpm/php.ini`:

```ini
upload_max_filesize = 256M
post_max_size = 260M
max_execution_time = 300
max_input_time = 300
memory_limit = 512M
```

Nginx:

```nginx
client_max_body_size 260M;
```

```bash
sudo systemctl restart php8.3-fpm nginx
```

## 3. Veritabanı

```bash
sudo mysql
```

```sql
CREATE DATABASE artworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'artworld_user'@'localhost' IDENTIFIED BY 'GÜÇLÜ_ÜRETİM_ŞİFRESİ';
GRANT ALL PRIVILEGES ON artworld.* TO 'artworld_user'@'localhost';
FLUSH PRIVILEGES;
```

```bash
mysql -u artworld_user -p artworld < /var/www/artworld-platform/database/migration.sql
mysql -u artworld_user -p artworld < /var/www/artworld-platform/database/seed.sql
```

Seed sonrası admin şifresini değiştirin.

## 4. Uygulama dosyaları

```bash
sudo mkdir -p /var/www/artworld-platform
sudo rsync -a ./artworld-platform/ /var/www/artworld-platform/
cd /var/www/artworld-platform/backend
cp .env.example .env
sudo nano .env
```

Production `.env`:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com
MEDIA_URL=https://media.example.com
DB_PASSWORD=GÜÇLÜ_ÜRETİM_ŞİFRESİ
CORS_ALLOWED_ORIGINS=https://admin.example.com
```

```bash
sudo chown -R www-data:www-data /var/www/artworld-platform
sudo chmod -R 775 /var/www/artworld-platform/backend/uploads
```

## 5. Nginx — API

`/etc/nginx/sites-available/api.example.com`:

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/artworld-platform/backend/public;
    index index.php;

    client_max_body_size 260M;

    # Security headers
    add_header X-Content-Type-Options nosniff always;
    add_header X-Frame-Options SAMEORIGIN always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 300;
    }

    location ~* /\. {
        deny all;
    }
}
```

## 6. Nginx — Admin

`/etc/nginx/sites-available/admin.example.com`:

```nginx
server {
    listen 80;
    server_name admin.example.com;
    root /var/www/artworld-platform/backend/admin;
    index index.php login.php;

    client_max_body_size 260M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_read_timeout 300;
    }

    # Admin’den uploads erişimi gerekiyorsa symlink veya alias kullanın
    location /uploads/ {
        alias /var/www/artworld-platform/backend/uploads/;
        location ~* \.php$ { deny all; }
    }
}
```

## 7. Nginx — Media (byte-range + HLS)

`/etc/nginx/sites-available/media.example.com`:

```nginx
server {
    listen 80;
    server_name media.example.com;
    root /var/www/artworld-platform/backend/uploads;

    # Byte-range (video seek)
    add_header Accept-Ranges bytes;

    # HLS MIME types
    types {
        application/vnd.apple.mpegurl m3u8;
        video/mp2t ts;
        video/mp4 mp4;
        image/jpeg jpg jpeg;
        image/png png;
        image/webp webp;
    }

    # Cache static media
    location ~* \.(mp4|m3u8|ts|jpg|jpeg|png|webp)$ {
        expires 7d;
        add_header Cache-Control "public";
        try_files $uri =404;
    }

    # Never execute PHP in uploads
    location ~* \.php$ {
        deny all;
    }

    location / {
        autoindex off;
        try_files $uri =404;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/api.example.com /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/admin.example.com /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/media.example.com /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 8. SSL (Let’s Encrypt)

```bash
sudo certbot --nginx -d api.example.com -d admin.example.com -d media.example.com
```

## 9. FFmpeg / HLS hazırlığı (opsiyonel, MVP zorunlu değil)

MP4 → HLS örneği:

```bash
ffmpeg -i input.mp4 \
  -codec: copy \
  -start_number 0 \
  -hls_time 10 \
  -hls_list_size 0 \
  -f hls \
  /var/www/artworld-platform/backend/uploads/hls/2026/07/video/index.m3u8
```

Sonuç URL: `https://media.example.com/hls/2026/07/video/index.m3u8`

İleride queue worker ile `UploadService` / conversion service katmanına eklenebilir.

## 10. Flutter production build

`lib/core/config/app_config.dart`:

```dart
static const String apiBaseUrl = 'https://api.example.com/api/v1';
static const String mediaBaseUrl = 'https://media.example.com';
```

```bash
flutter build apk --release
flutter build appbundle
flutter build ipa
```

Android `applicationId` değiştirme: `android/app/build.gradle.kts`  
iOS bundle id: Xcode → Runner target

Production’da cleartext HTTP kapalı tutun; ATS/Network Security yalnızca HTTPS kullanın.

## 11. Güvenlik kontrol listesi

- [ ] `APP_DEBUG=false`
- [ ] Admin şifresi değiştirildi
- [ ] `.env` web’den erişilemez
- [ ] Uploads klasöründe PHP çalıştırma engelli
- [ ] HTTPS zorunlu
- [ ] Firewall (ufw): 22, 80, 443
- [ ] Düzenli MySQL yedek
