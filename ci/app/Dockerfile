# Imagen de ejecución de Mayoro (Laravel 12 + PHP 8.4 + SQLite).
#
# El contexto de build es la RAÍZ del proyecto:
#   docker build -f ci/app/Dockerfile .
#
# Debe construirse tras `npm run build` para que public/build
# (assets compilados por Vite) exista en el contexto.
FROM php:8.4-cli

WORKDIR /var/www/html

# Composer descarga los paquetes como .zip: necesita el binario unzip
# (o la extensión zip, que la imagen oficial no trae por defecto) y git.
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

# Composer desde la imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# 1) Dependencias de producción primero (aprovecha la caché de capas de Docker)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader

# 2) Código de la aplicación (incluye public/build con los assets de Vite)
COPY . .

# 3) Directorios de runtime y enlace público de almacenamiento
#    (imprescindible para las imágenes de producto, ver README)
RUN mkdir -p storage/app/public \
               storage/framework/cache/data \
               storage/framework/sessions \
               storage/framework/testing \
               storage/framework/views \
               storage/logs \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

# 4) Script de arranque: migraciones + seed inicial + servidor HTTP
COPY ci/app/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
