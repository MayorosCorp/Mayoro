#!/bin/sh
# Arranque del contenedor de Mayoro:
#   - valida APP_KEY
#   - crea la base SQLite si es la primera ejecución (y siembra datos demo)
#   - aplica migraciones (idempotente)
#   - levanta el servidor HTTP en el puerto 8080
set -e

DB_PATH="${DB_DATABASE:-storage/database.sqlite}"

if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY no definida. Provee APP_KEY en el entorno del contenedor." >&2
    exit 1
fi

FIRST_RUN=0
if [ ! -f "$DB_PATH" ]; then
    FIRST_RUN=1
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
fi

php artisan migrate --force

if [ "$FIRST_RUN" = "1" ]; then
    echo "Primera ejecucion: sembrando datos de demostracion..."
    php artisan db:seed --force
fi

exec php artisan serve --host=0.0.0.0 --port=8080
