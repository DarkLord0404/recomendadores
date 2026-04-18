#!/bin/bash
# =============================================================================
# Script de despliegue - MediRecomienda en VPS (Ubuntu 22.04 / Debian 12)
# Uso: bash deploy.sh
# =============================================================================

set -e

APP_DIR="/var/www/medirecomenda"
PHP_VERSION="8.4"

echo "=== [1/8] Actualizando el sistema ==="
sudo apt-get update && sudo apt-get upgrade -y

echo "=== [2/8] Instalando dependencias del servidor ==="
sudo apt-get install -y \
    nginx \
    mysql-server \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-tokenizer \
    php${PHP_VERSION}-gd \
    unzip \
    git \
    curl

echo "=== [3/8] Instalando Composer ==="
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

echo "=== [4/8] Clonando / actualizando el proyecto ==="
if [ -d "$APP_DIR" ]; then
    cd "$APP_DIR"
    git pull origin main
else
    sudo git clone https://github.com/TU_USUARIO/TU_REPO.git "$APP_DIR"
    cd "$APP_DIR"
fi

echo "=== [5/8] Instalando dependencias PHP ==="
sudo composer install --no-dev --optimize-autoloader

echo "=== [6/8] Configurando permisos ==="
sudo chown -R www-data:www-data "$APP_DIR"
sudo chmod -R 755 "$APP_DIR"
sudo chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "=== [7/8] Configurando Laravel ==="
cd "$APP_DIR"

# Copiar .env de producción (debe estar en el servidor como .env.production)
if [ ! -f .env ]; then
    cp .env.production .env
fi

php artisan key:generate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force

echo "=== [8/8] Configurando Nginx ==="
sudo bash -c "cat > /etc/nginx/sites-available/medirecomenda << 'NGINX'
server {
    listen 80;
    server_name TU_DOMINIO_O_IP;
    root ${APP_DIR}/public;

    add_header X-Frame-Options \"SAMEORIGIN\";
    add_header X-Content-Type-Options \"nosniff\";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX"

sudo ln -sf /etc/nginx/sites-available/medirecomenda /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

echo ""
echo "============================================="
echo "  Despliegue completado con éxito!"
echo "  Recuerda:"
echo "  1. Configurar .env con tus credenciales reales"
echo "  2. Agregar OPENAI_API_KEY a .env"
echo "  3. Configurar SSL con: sudo certbot --nginx -d TU_DOMINIO"
echo "============================================="
