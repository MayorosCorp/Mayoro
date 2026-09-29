# Mayoro — Plataforma B2B de Cotizaciones

Mayoro es un sistema web B2B para la gestión integral del ciclo de cotización-compra entre empresas: permite registrar productos, proveedores, generar cotizaciones, convertirlas en pedidos, controlar el inventario y emitir reportes de gestión.

## Propósito del sistema

- **Catálogo de productos** gestionado por SKU, con precios y proveedor asociado.
- **Directorio de proveedores** con datos de contacto normativos (RUC, razón social).
- **Flujo de cotizaciones** desde la solicitud hasta la conversión en pedido.
- **Pedidos de compra** vinculados a cotizaciones y proveedores con fechas de entrega.
- **Inventario** con entradas, salidas y ajustes de stock.
- **Reportes** operativos por tipo (cotizaciones, pedidos, inventario, proveedores) y rango de fechas.
- **Autenticación** de empresas con acceso restringido (`auth` middleware) al resto del portal.

## Tecnologías

- **Laravel 12** (PHP 8.2+)
- **SQLite** por defecto (reemplazable vía `.env` por MySQL/PostgreSQL)
- **Blade** + **Vite** para el frontend

## Arquitectura de módulos

```
app/Http/Controllers/
├── AuthController.php        # Login, registro y logout
├── DashboardController.php   # Panel principal con métricas
├── ProductController.php     # CRUD de productos
├── SupplierController.php    # CRUD de proveedores
├── QuoteController.php       # CRUD de cotizaciones
├── OrderController.php       # CRUD de pedidos
├── InventoryController.php   # Movimientos de stock
└── ReportController.php      # Generación de reportes

resources/views/
├── layouts/app.blade.php     # Layout base compartido
├── auth/                     # Login y registro
├── dashboard/                # Panel principal
├── products/                 # Índice, crear, editar, detalle
├── suppliers/                # Índice, crear, editar, detalle
├── quotes/                   # Índice, crear, editar, detalle
├── orders/                   # Índice, crear, editar, detalle
├── inventory/                # Índice, crear, editar
└── reports/                  # Índice y generador
```

Las rutas viven en `routes/web.php` y separan explícitamente los verbos HTTP: `GET` para vistas y `POST`/`PUT`/`DELETE` para acciones, apuntando cada una a un controlador dedicado (no se usan `Route::match` de verbos combinados).

## Instalación local

Requisitos: PHP >= 8.2, Composer 2 y Node.js (para Vite).

```bash
# 1. Clonar el repositorio
git clone <repo-url> mayoro
cd mayoro

# 2. Dependencias de PHP y JavaScript
composer install
npm install

# 3. Configuración del entorno
cp .env.example .env
php artisan key:generate

# 4. Base de datos (SQLite por defecto)
touch database/database.sqlite
php artisan migrate

# 5. Enlazar el almacenamiento público de archivos
# Imprescindible: la carga de imagen de producto (HU-02) guarda los archivos
# en storage/app/public y los expone a través de public/storage. Ese enlace es
# un archivo del sistema y está en .gitignore, por lo que no viaja en el
# repositorio. Sin este paso la aplicación funciona, pero toda imagen de
# producto sale como enlace roto.
php artisan storage:link

# 6. Levantar el servidor de desarrollo
php artisan serve
# En otra terminal:
npm run dev
```

Abre `http://localhost:8000` en el navegador.

Cuenta de prueba con datos de ejemplo, creada por el seeder:

```
ventas@distribuidora-andina.pe / password   (distribuidor, puede publicar)
compras@bodegaelcomercio.pe / password     (bodega, solo compra y cotiza)
```

## Estrategia de ramas: Gitflow

- `main` → producción (estable, solo mediante merges de release).
- `develop` → integración continua; es la rama de trabajo.
- `feature/*` → nuevas funcionalidades (se crean desde `develop`).
- `release/*` → preparación de versiones.
- `hotfix/*` → correcciones urgentes en producción.

Regla de oro: **nunca** se hace commit directo a `main`; todo llega mediante *pull requests* o merges controlados desde `develop`.

## Convención de commits: Conventional Commits 1.0.0

Formato obligatorio:

```
<tipo>(<alcance>): <descripción en imperativo y minúsculas>
```

Ejemplos válidos:

```bash
feat(quotes): add quote approval workflow
fix(auth): prevent session fixation on login
refactor(routes): separate HTTP verbs and map dedicated controllers
docs(readme): add project setup and commit convention guidelines
```

### Tipos permitidos

| Tipo            | Uso                                                        |
|-----------------|------------------------------------------------------------|
| `feat`          | Nueva funcionalidad                                        |
| `fix`           | Corrección de errores                                      |
| `refactor`      | Cambio de código sin alterar comportamiento                |
| `docs`          | Cambios en documentación                                   |
| `style`         | Formato, espacios, punto y coma (sin cambios de lógica)    |
| `test`          | Adición o modificación de pruebas                          |
| `chore`         | Tareas de mantenimiento, dependencias, config de repo      |
| `build`         | Cambios en el sistema de build o dependencias externas     |
| `ci`            | Cambios en CI y pipelines                                  |
| `perf`          | Mejoras de rendimiento                                     |
| `revert`        | Reversión de un commit anterior                            |

### Reglas

- La descripción va en **imperativo** y **minúsculas** (p. ej. `add`, no `added/_adds/Added`).
- Para breaking changes, agregar `!` tras el tipo/alcance: `feat(quotes)!: drop pdf export`.
- El alcance es opcional pero recomendado (módulo afectado).
- Un commit debe representar **una única** unidad de cambio lógica.

## Pipeline de calidad

```bash
./vendor/bin/pint            # Formato de código (PSR-12 / Laravel preset)
./vendor/bin/pint --test     # Verificar que el formato es correcto
php artisan route:list       # Listar rutas registradas
php artisan view:cache       # Compilar todas las vistas Blade
php artisan test             # Ejecutar la suite de pruebas
```

## Notas de seguridad

- `.env`, `vendor/`, `node_modules` y otros artefactos locales están excluidos vía `.gitignore`.
- Todos los módulos de negocio están protegidos por el middleware `auth`.
- Las contraseñas se procesan mediante el `Hash` manager de Laravel en el flujo de autenticación.