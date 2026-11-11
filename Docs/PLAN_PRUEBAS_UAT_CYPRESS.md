# Plan de pruebas UAT con Cypress — Mayoro

## 1. Propósito

Este documento propone pruebas de aceptación adicionales basadas en el caso
`UAT-001 — Registro del producto`. Las pruebas validan flujos visibles para el
usuario final y reglas de negocio importantes del sistema B2B Mayoro.

## 2. Criterios generales

- **Herramienta:** Cypress.
- **Nivel:** prueba de aceptación end-to-end.
- **Prioridad:** alta, salvo donde se indique lo contrario.
- **Usuario QA:** Carlos Eduardo Loaiza Martinez.
- **Cuenta Empresario:** `ventas@distribuidora-andina.pe` / `password`.
- **Selectores:** preferir `data-testid`.
- **Evidencia:** validar URL, mensajes visibles, tarjetas del catálogo, estados
  y controles disponibles.
- **Regla:** las pruebas deben crear datos únicos o limpiar sus datos para poder
  repetirse sin colisiones.

## 3. Pruebas propuestas

### UAT-002 — Rechazo de producto con datos obligatorios incompletos

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Impedir el registro cuando faltan datos obligatorios |
| Precondición | Empresario autenticado |
| Datos | Formulario vacío o con campos obligatorios incompletos |
| Pasos | Abrir alta, dejar campos vacíos, pulsar publicar |
| Resultado esperado | Se muestran errores visibles y no se crea el producto |
| Criterio de aceptación | La validación bloquea el envío sin respuesta exitosa |

Validar especialmente nombre, categoría, presentación, precio y stock. El MOQ
se renderiza con valor inicial `1`, por lo que no está vacío en el formulario;
su validación de valores inválidos se cubre en `UAT-003`.

### UAT-003 — Rechazo de precio inválido

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Mantener la regla de precio positivo y máximo dos decimales |
| Precondición | Empresario autenticado |
| Datos | Precio `0`, negativo y `56.005` |
| Pasos | Completar el formulario con cada precio inválido y guardar |
| Resultado esperado | Se muestra un error de validación y no se registra el producto |
| Criterio de aceptación | No se persiste un precio inválido ni se redondea silenciosamente |

### UAT-004 — Validación de stock respecto al MOQ

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Evitar productos que prometan una cantidad menor al pedido mínimo |
| Precondición | Empresario autenticado |
| Datos | MOQ `5`, stock `3` |
| Pasos | Completar datos válidos, establecer stock menor al MOQ y guardar |
| Resultado esperado | Se muestra el error de stock y no se registra el producto |
| Criterio de aceptación | Se conserva la regla: stock igual a `0` o mayor/igual al MOQ |

### UAT-005 — Registro válido sin imagen

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Confirmar que la imagen es opcional |
| Precondición | Empresario autenticado |
| Datos | Producto válido sin archivo de imagen |
| Pasos | Completar el formulario, no seleccionar imagen y guardar |
| Resultado esperado | Producto registrado y tarjeta muestra `Sin imagen` |
| Criterio de aceptación | La ausencia de imagen no bloquea el registro |

### UAT-006 — Rechazo de imagen no permitida

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Proteger la carga de archivos |
| Precondición | Empresario autenticado |
| Datos | Archivo con extensión/formato no permitido o mayor a 2 MB |
| Pasos | Seleccionar archivo inválido y pulsar publicar |
| Resultado esperado | Se muestra un error visible sobre la imagen |
| Criterio de aceptación | El archivo no se almacena y el producto no se registra |

### UAT-007 — Registro con tramo de precio por volumen

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Verificar descuentos por volumen válidos |
| Precondición | Empresario autenticado |
| Datos | Precio base `120`, MOQ `5`, tramo desde `20` a `110` |
| Pasos | Completar producto, agregar tramo, guardar y abrir catálogo |
| Resultado esperado | Producto registrado y tramo visible en la tarjeta/ficha |
| Criterio de aceptación | El tramo inicia sobre el MOQ y tiene precio menor al base |

### UAT-008 — Rechazo de tramo que rompe la regla de negocio

| Campo | Descripción |
|---|---|
| Módulo | Registro del producto |
| Objetivo | Rechazar descuentos inconsistentes |
| Precondición | Empresario autenticado |
| Datos | Tramo con umbral menor o igual al MOQ, o precio mayor al base |
| Pasos | Completar producto, agregar tramo inválido y guardar |
| Resultado esperado | Error visible y producto no registrado |
| Criterio de aceptación | No se persisten tramos que contradigan RN-04 |

### UAT-009 — Usuario no autenticado no puede registrar productos

| Campo | Descripción |
|---|---|
| Módulo | Control de acceso al catálogo |
| Objetivo | Proteger el registro de productos |
| Precondición | Ninguna |
| Datos | URL `/products/create` |
| Pasos | Visitar la URL sin sesión |
| Resultado esperado | Redirección a `/login` |
| Criterio de aceptación | No se muestra el formulario de alta a visitantes |

### UAT-010 — Usuario Persona Natural no puede registrar productos

| Campo | Descripción |
|---|---|
| Módulo | Control de acceso/RBAC |
| Objetivo | Impedir que un usuario comprador publique productos |
| Precondición | Sesión iniciada como Persona Natural |
| Datos | Cuenta `compras@bodegaelcomercio.pe` / `password` |
| Pasos | Iniciar sesión y visitar `/products/create` |
| Resultado esperado | Acceso denegado o ausencia de la acción de publicar |
| Criterio de aceptación | Solo un Empresario puede registrar productos |

### UAT-011 — Búsqueda y visualización del producto registrado

| Campo | Descripción |
|---|---|
| Módulo | Catálogo mayorista |
| Objetivo | Confirmar que un producto publicado puede localizarse y consultarse |
| Precondición | Producto registrado correctamente |
| Datos | Nombre único del producto |
| Pasos | Abrir catálogo, buscar por nombre, abrir detalle |
| Resultado esperado | La tarjeta y la ficha muestran los datos registrados |
| Criterio de aceptación | Nombre, categoría, precio, MOQ, stock e imagen coinciden |

### UAT-012 — Edición del producto publicado

| Campo | Descripción |
|---|---|
| Módulo | Administración del producto |
| Objetivo | Confirmar que el Empresario puede actualizar su producto |
| Precondición | Empresario propietario autenticado y producto activo |
| Datos | Nuevo precio, stock o descripción válidos |
| Pasos | Abrir editar, modificar datos, guardar y revisar catálogo |
| Resultado esperado | Se muestra mensaje de actualización y los cambios aparecen |
| Criterio de aceptación | El precio unitario se recalcula y el producto conserva su identidad |

### UAT-013 — Baja del producto del catálogo

| Campo | Descripción |
|---|---|
| Módulo | Administración del producto |
| Objetivo | Confirmar que el Empresario puede pausar su producto |
| Precondición | Empresario propietario autenticado y producto activo |
| Pasos | Abrir edición/detalle, dar de baja, consultar catálogo |
| Resultado esperado | Producto marcado como pausado/sin stock según la regla vigente |
| Criterio de aceptación | No se ofrece para cotización y la información de estado es visible |

### UAT-014 — Empresario solo administra sus propios productos

| Campo | Descripción |
|---|---|
| Módulo | Seguridad de productos |
| Objetivo | Evitar que un Empresario edite o elimine productos ajenos |
| Precondición | Producto perteneciente a otro Empresario |
| Pasos | Intentar abrir o modificar el producto ajeno |
| Resultado esperado | Respuesta `403` o bloqueo equivalente |
| Criterio de aceptación | El propietario original conserva el control del producto |

## 4. Orden recomendado de implementación

1. `UAT-002`: campos obligatorios.
2. `UAT-003`: precio.
3. `UAT-004`: stock y MOQ.
4. `UAT-005`: alta sin imagen.
5. `UAT-006`: imagen inválida.
6. `UAT-007` y `UAT-008`: tramos por volumen.
7. `UAT-009` y `UAT-010`: roles y acceso.
8. `UAT-011`, `UAT-012` y `UAT-013`: ciclo de vida del producto.
9. `UAT-014`: autorización entre empresarios.

## 5. Resultado esperado de la matriz

Las pruebas deben demostrar que Mayoro:

- registra correctamente productos válidos;
- muestra el producto en el catálogo;
- conserva las reglas de precio, stock, MOQ y descuentos;
- rechaza entradas inválidas con mensajes visibles;
- valida imágenes y protege el almacenamiento;
- diferencia permisos de Empresarios, Personas Naturales y visitantes;
- permite administrar únicamente productos propios.
