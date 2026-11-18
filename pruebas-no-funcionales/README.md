# Pruebas no funcionales de Mayoro

## Rendimiento del catálogo

Plan JMeter: `rendimiento-catalogo-mayoro.jmx`.

### Configuración

- Host y puerto predeterminados: `localhost:8000`.
- Categoría predeterminada: `Abarrotes`.
- El plan comienza con **1 usuario y 1 iteración** para limitar la carga inicial. No se ha ejecutado como parte de su preparación.
- En JMeter, cambie `HOST`, `PORT` y `CATEGORY` en las variables del Test Plan si su entorno usa otros valores. Ajuste usuarios, rampa y duración únicamente conforme al protocolo de prueba aprobado.
- La categoría debe existir en el catálogo. Para obtener una medición útil, precaliente el ambiente, tenga datos representativos y configure varias iteraciones/duración; la configuración de seguridad actual no es suficiente para evaluar estadísticamente el P95.
- `q` usa `arroz` por defecto y se puede cambiar con `-Jquery=termino`. El término debe existir en el catálogo para obtener resultados representativos.

### Criterios del caso

- P95 de respuesta menor a 2.000 ms.
- Tasa de errores HTTP menor al 1 %.
- Las tres solicitudes del flujo son `GET /products`, `GET /products?q=...` y `GET /products?categoria=...`.
- Cada solicitud incluye una aserción de respuesta HTTP 200. El resumen de resultados muestra tiempos, códigos y errores.

Los umbrales son metas propuestas para evaluación, no requisitos aprobados ni resultados medidos. El plan no considera fallos de aserción como error HTTP; revisar también la columna de aserciones fallidas en el reporte. Para presentar evidencia estadística, guardar los resultados `.jtl` y un reporte HTML generado en una ejecución controlada; no guardar esos resultados en el repositorio sin autorización.

## RNF-02 — Capacidad de carga con k6

Script: `rnf-02-carga-catalogo-k6.js`.

- Secuencia por iteración: `GET /products`, búsqueda `GET /products?q=...` y filtro `GET /products?categoria=...`.
- Carga predeterminada: rampa progresiva hasta 100 usuarios virtuales (VUs), 5 minutos de permanencia en 100 VUs y descenso. Duración total aproximada: 14 minutos.
- Umbrales configurados: P95 de solicitudes HTTP < 2.000 ms, tasa de solicitudes fallidas < 1 % y checks > 99 %.
- Objetivo por defecto: `http://localhost:8000`; categoría `Abarrotes`; término de búsqueda `arroz`.
- El destino, categoría, búsqueda y tiempos de las etapas se pueden adaptar con `TARGET`, `CATEGORY`, `SEARCH_TERM`, `RAMP_DURATION`, `HOLD_DURATION` y `RAMP_DOWN_DURATION`.
- El script es configuración reutilizable; su ejecución local previa generó un resumen que se excluye de Git. Revisar entorno y datos antes de ejecutar. Nunca apuntar a producción sin autorización expresa.

Para ejecutar manualmente desde esta carpeta, cuando se apruebe la prueba:

```powershell
k6 run .\rnf-02-carga-catalogo-k6.js
```

Al finalizar, el script guarda un resumen JSON como `rnf-02-k6-summary.json` en el directorio de trabajo desde el que se ejecutó. k6 también imprime métricas de VUs, solicitudes por segundo, latencias y fallos en la terminal. El reporte es evidencia de la ejecución y debe archivarse fuera del código fuente según las reglas del equipo.

## RNF-03 — Seguridad: control de acceso por roles (OWASP ZAP)

Archivos del caso:

- `docker-compose.rnf03.yml`: entorno desechable de Mayoro con SQLite dentro del contenedor y ZAP disponible bajo el perfil `security-review`.
- `rnf-03-rbac-zap.http`: solicitudes manuales de lectura y escritura para visitante anónimo y Persona natural. Se puede abrir con la extensión REST Client de VS Code; para el caso autenticado, reemplazar el marcador de cookie por la cookie obtenida al iniciar sesión con la cuenta demo Persona natural del entorno desechable.
- `rnf03-rbac-evidencia.sql`: consulta de conteo posterior, solo para la SQLite desechable.

### Resultado esperado

- GET `/products/create`: visitante anónimo redirigido a `/login`; Persona natural autenticada recibe HTTP 403.
- POST `/products`: visitante anónimo redirigido al login; Persona natural recibe HTTP 403.
- Después de las solicitudes denegadas, el conteo del producto `RNF03-Prueba-Autorizacion` debe ser 0.
- El Empresario es el rol autorizado, pero este caso negativo no necesita crear producto como parte de la comprobación.

### Preparación aislada

Desde `Mayoro/`:

```powershell
docker compose -f .\pruebas-no-funcionales\docker-compose.rnf03.yml --profile security-review up -d --build
docker compose -f .\pruebas-no-funcionales\docker-compose.rnf03.yml ps
```

La aplicación de prueba queda en `http://localhost:8081` y ZAP en `http://localhost:8090`, si Docker Desktop publica correctamente los puertos en el host. Compose ejecuta migraciones limpias y el seeder únicamente dentro de una SQLite efímera del contenedor; no apunta a `mayoro_db`. La red Compose es interna.

En ZAP, configura el proxy manual `localhost:8090` y navega a `http://localhost:8081` desde el navegador de prueba; realiza solo las solicitudes del archivo HTTP. Comprueba primero que `docker compose ... ps` muestre los puertos publicados. Si Docker Desktop no publica puertos al host, la GUI local de ZAP en Windows no alcanza el nombre privado `http://app:8080`; abre el navegador en un contenedor conectado a la red de prueba o habilita los puertos con la configuración de Docker Desktop. Mantén el escáner pasivo y no uses “Active Scan”, fuzzing ni spider contra otros destinos. ZAP es auxiliar para observar solicitudes/respuestas: la evidencia decisiva es el código HTTP y el conteo en SQLite.

Cuando termines, elimina el entorno:

```powershell
docker compose -f .\pruebas-no-funcionales\docker-compose.rnf03.yml down --remove-orphans
```

La configuración actual del entorno local de Mayoro indica `DB_DATABASE=mayoro_db`; no apuntes estos POST a `localhost:8000` ni a producción. No ejecutes las solicitudes de escritura hasta verificar que `docker compose ps` muestra el servicio `app` sano y que está usando SQLite desechable.

### Verificar y abrir sin ejecutar

Desde esta carpeta:

```powershell
jmeter -v
jmeter -t .\rendimiento-catalogo-mayoro.jmx
```

El segundo comando abre la interfaz de JMeter; **no presione Start** hasta contar con autorización y confirmar el entorno. No usar la opción non-GUI ni iniciar carga contra producción sin autorización.
