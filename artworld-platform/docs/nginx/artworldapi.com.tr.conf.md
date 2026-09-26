# Nginx — unified host artworldapi.com.tr

Single domain for Web + Admin + API.

## Target map

| URL | App |
|-----|-----|
| `https://artworldapi.com.tr/` | Web (`web/public`) |
| `https://artworldapi.com.tr/login` | Admin login |
| `https://artworldapi.com.tr/admin/` | Admin panel |
| `https://artworldapi.com.tr/api/v1/` | REST API |
| `https://media.artworldapi.com.tr/` | Optional media / File Server reverse proxy |

## Example config

```nginx
# /etc/nginx/sites-available/artworldapi.com.tr.conf

upstream artworld_php {
    server unix:/run/php/php8.3-fpm.sock;
}

server {
    listen 80;
    listen [::]:80;
    server_name artworldapi.com.tr www.artworldapi.com.tr;
    return 301 https://artworldapi.com.tr$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name artworldapi.com.tr;

    # ssl_certificate     /etc/letsencrypt/live/artworldapi.com.tr/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/artworldapi.com.tr/privkey.pem;
    # include /etc/letsencrypt/options-ssl-nginx.conf;

    client_max_body_size 512M;

    # --- API ---
    location ^~ /api/ {
        root /var/www/artworld-platform/backend/public;
        try_files $uri /index.php$is_args$args;
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME /var/www/artworld-platform/backend/public/index.php;
            include fastcgi_params;
            fastcgi_read_timeout 300;
        }
    }

    # Media from API uploads (Accept-Ranges via nginx)
    location ^~ /images/ {
        alias /var/www/artworld-platform/backend/uploads/images/;
        expires 30d;
        add_header Accept-Ranges bytes;
        add_header Access-Control-Allow-Origin *;
    }
    location ^~ /videos/ {
        alias /var/www/artworld-platform/backend/uploads/videos/;
        expires 7d;
        add_header Accept-Ranges bytes;
        add_header Access-Control-Allow-Origin *;
        types { video/mp4 mp4; video/webm webm; application/vnd.apple.mpegurl m3u8; }
    }
    location ^~ /thumbnails/ {
        alias /var/www/artworld-platform/backend/uploads/thumbnails/;
        expires 30d;
        add_header Accept-Ranges bytes;
    }

    # --- Admin ---
    location = /login {
        return 302 /admin/login.php;
    }
    location ^~ /admin/ {
        alias /var/www/artworld-platform/backend/admin/;
        index index.php;
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            include fastcgi_params;
            fastcgi_read_timeout 600;
        }
        location ~* \.(env|log|sql)$ { deny all; }
    }

    # --- Web frontend ---
    location /assets/ {
        alias /var/www/artworld-platform/web/assets/;
        expires 7d;
    }
    location / {
        root /var/www/artworld-platform/web/public;
        try_files $uri $uri/ /index.php$is_args$args;
        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_pass artworld_php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
        }
    }

    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml;
}

# Optional media subdomain → File Server origin
server {
    listen 443 ssl http2;
    server_name media.artworldapi.com.tr;
    # ssl_certificate ...
    client_max_body_size 1024M;

    # Prefer proxying to File Server when online; else local uploads
    # location / {
    #     proxy_pass http://193.35.155.55;
    #     proxy_set_header Host $host;
    #     proxy_http_version 1.1;
    #     proxy_request_buffering off;
    # }

    location / {
        root /var/www/artworld-platform/backend/uploads;
        add_header Accept-Ranges bytes;
        types { video/mp4 mp4; }
    }
}
```

Enable:

```bash
sudo ln -sf /etc/nginx/sites-available/artworldapi.com.tr.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d artworldapi.com.tr -d media.artworldapi.com.tr
```

## Current production note (2026-09-25)

`https://artworldapi.com.tr/` currently returns API-style JSON 404 (API-only vhost).
`https://api.artworldapi.com.tr/api/v1/*` returns HTTP 200.

Deploy this config on the production host to merge Web under the same apex domain.
Until then, keep serving API on `api.` and prepare DNS/Nginx cutover.
