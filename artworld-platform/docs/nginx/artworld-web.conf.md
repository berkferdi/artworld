# Nginx — Art World Web (www.artworld.com.tr)

Cloud / local agents may not have Nginx. On production:

```nginx
# /etc/nginx/sites-available/artworld-web.conf

server {
    listen 80;
    listen [::]:80;
    server_name artworld.com.tr;
    return 301 https://www.artworld.com.tr$request_uri;
}

server {
    listen 80;
    listen [::]:80;
    server_name www.artworld.com.tr;
    return 301 https://www.artworld.com.tr$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.artworld.com.tr;

    # ssl_certificate /etc/letsencrypt/live/www.artworld.com.tr/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/www.artworld.com.tr/privkey.pem;
    # include /etc/letsencrypt/options-ssl-nginx.conf;

    root /var/www/artworld-platform/web/public;
    index index.php;

    client_max_body_size 8M;

    location /assets/ {
        alias /var/www/artworld-platform/web/assets/;
        expires 7d;
        add_header Cache-Control "public";
        access_log off;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(env|log|sql)$ {
        deny all;
    }

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
}
```

Enable:

```bash
sudo ln -sf /etc/nginx/sites-available/artworld-web.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d www.artworld.com.tr -d artworld.com.tr
```

Local PHP built-in server (dev):

```bash
cd /var/www/artworld-platform/web
php -S 0.0.0.0:8081 -t public public/router.php
```
