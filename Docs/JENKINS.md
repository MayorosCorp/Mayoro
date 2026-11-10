# Integración y despliegue continuo con Jenkins

Pipeline CI/CD de Mayoro: cada push a `develop` compila, valida y despliega la
aplicación en Docker sobre el mismo equipo donde corre Jenkins.

## Arquitectura

```
GitHub (MayorosCorp/Mayoro, rama develop)
        │  poll SCM cada ~5 min
        ▼
┌─────────────────────────────┐        ┌──────────────────────────────┐
│ mayoro-jenkins (contenedor) │        │ mayoro-app (contenedor)      │
│  PHP 8.4 + Composer         │ docker │  Imagen mayoro/app:<build>   │
│  Node 22 + Vite             │───────▶│  http://localhost:8000       │
│  Docker CLI (socket host)   │        │  Volumen mayoro_storage      │
└─────────────────────────────┘        └──────────────────────────────┘
```

- **Jenkins** corre en un contenedor con todo el toolchain (PHP, Composer, Node,
  Docker CLI) y usa el daemon Docker del host a través de `/var/run/docker.sock`.
- **La aplicación** se construye como imagen (`ci/app/Dockerfile`) y se ejecuta
  como contenedor hermano en la red `mayoro_default`.
- **Datos persistentes**: `mayoro_storage` (base SQLite, sesiones, imágenes de
  producto) y `jenkins_home` (configuración de Jenkins, builds y la `APP_KEY`).

## Requisitos

- Docker Desktop iniciado (Windows/macOS) o Docker Engine (Linux).
- Puerto `8080` (Jenkins) y `8000` (aplicación) libres.
- Repositorio accesible desde Jenkins (este repo es público: no requiere
  credenciales).

## Puesta en marcha (una sola vez)

```bash
# 1. Construir y levantar Jenkins (la primera vez tarda varios minutos)
cd ci/jenkins
docker compose up -d --build

# 2. Comprobar que está arriba
docker ps
# → mayoro-jenkins ... 0.0.0.0:8080->8080/tcp
```

Abre <http://localhost:8080>. Usuario `admin` y contraseña inicial:

```bash
docker exec mayoro-jenkins cat /var/jenkins_home/secrets/initialAdminPassword
```

> El asistente de primera ejecución está desactivado (setup headless) y los
> plugins necesarios (Git, Pipeline, JUnit) ya vienen instalados en la imagen.

### Crear el job y lanzar el primer build

Con el script incluido (usa la API REST de Jenkins):

```powershell
powershell -ExecutionPolicy Bypass -File ci/jenkins/setup-job.ps1
```

O manualmente en la UI: **Nueva tarea → Pipeline → Pipeline script from SCM**,
URL `https://github.com/MayorosCorp/Mayoro.git`, rama `*/develop`,
script `Jenkinsfile`.

El build queda en <http://localhost:8080/job/mayoro> y la aplicación desplegada
en <http://localhost:8000>.

## Qué hace el pipeline

| Etapa | Comando | Qué valida |
|---|---|---|
| Dependencias | `composer install` | `composer.json`/`composer.lock` consistentes |
| Frontend | `npm ci` + `npm run build` | assets de Vite compilables |
| Calidad | `vendor/bin/pint --test` | estilo PSR-12 / preset Laravel |
| Tests | `vendor/bin/phpunit --log-junit` | suite completa (SQLite en memoria) + reporte JUnit |
| Imagen | `docker build -f ci/app/Dockerfile` | imagen de producción reproducible |
| Despliegue | `docker run mayoro-app` | contenedor nuevo en `localhost:8000` |
| Salud | `curl http://mayoro-app:8080` | la app responde HTTP 200 |

El reporte de pruebas aparece en la pestaña **Test Result** de cada build.

## Flujo de trabajo diario

1. Trabaja en una rama `feature/*` y mergea a `develop` (Gitflow).
2. `git push origin develop` → Jenkins detecta el cambio (sondeo cada ~5 min).
3. Para ejecutar sin esperar: **Build Now** en la UI del job.

> Para builds instantáneos se necesitaría un webhook de GitHub, lo que exige
> exponer Jenkins a Internet (p. ej. con un túnel). No es necesario en local.

## Despliegue manual (sin Jenkins)

```bash
# Crear la APP_KEY una sola vez
php artisan key:generate --show   # copiar el valor
# → ci/app/deploy.env con la línea: APP_KEY=base64:...

docker compose up -d --build
```

Si Jenkins ya desplegó `mayoro-app`, detén ese contenedor antes
(`docker rm -f mayoro-app`) para evitar conflicto de nombre y puerto.

## Datos demo

En la primera ejecución la base se siembra automáticamente:

- `ventas@distribuidora-andina.pe` / `password` (Empresario)
- `compras@bodegaelcomercio.pe` / `password` (Persona natural)

## Persistencia y reinicio

```bash
# Volúmenes en uso
docker volume ls | Select-String mayoro

# Reiniciar Jenkins sin perder nada
cd ci/jenkins && docker compose restart

# Borrar Jenkins completo (configuración, builds y APP_KEY)
cd ci/jenkins && docker compose down -v

# Recrear solo la aplicación (borra base y sesiones)
docker rm -f mayoro-app
docker volume rm mayoro_storage
```

## Solución de problemas

| Síntoma | Causa / solución |
|---|---|
| El job falla con "Jenkinsfile not found" | El commit con el pipeline no está en `develop` en GitHub |
| `permission denied` en `/var/run/docker.sock` | El contenedor corre como `root` (ya configurado en `ci/jenkins/docker-compose.yml`) |
| Puerto 8080 u 8000 ocupado | Cambia el mapeo en los `docker-compose.yml` respectivos |
| La app responde 500 y el log dice "Unsupported cipher" | `APP_KEY` inválida; borra `jenkins_home/mayoro-deploy.env` y vuelve a desplegar |
| Primer build muy lento | Normal: descarga dependencias de Composer/npm por primera vez |
| Cambios en `ci/jenkins/Dockerfile` no aplican | `docker compose up -d --build` |

## Hacia un entorno de producción real

El despliegue actual es de demostración (servidor embebido de Laravel). Para
producción se recomienda: Nginx + PHP-FPM, base de datos gestionada
(MySQL/PostgreSQL), `APP_KEY` como credencial de Jenkins (`withCredentials`),
webhook de GitHub, HTTPS en un proxy inverso y despliegue azul/verde con dos
contenedores.
