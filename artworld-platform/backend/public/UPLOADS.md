# Uploads serving notes
#
# Preferred (Nginx):
#   location /uploads/ {
#       alias /var/www/artworld-platform/backend/uploads/;
#       autoindex off;
#       location ~* \.php$ { deny all; }
#   }
#
# Fallback PHP router:
#   /media.php?path=images/2026/07/file.jpg
#   (see public/media.php)
#
# Apache DocumentRoot=public may use RewriteRule in public/.htaccess
# to map /uploads/* → ../uploads/*
#
# MEDIA_URL in .env should point to the public origin that serves these files,
# e.g. MEDIA_URL=http://193.35.155.149 so Media::url('/images/x.jpg') becomes
# http://193.35.155.149/images/x.jpg or http://.../uploads/images/x.jpg depending
# on how you alias paths. UploadService stores paths like /images/YYYY/MM/file.ext
# under backend/uploads/images/...
