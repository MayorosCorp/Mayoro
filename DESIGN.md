# Design

Decisiones visuales duraderas de Mayoro. `PRODUCT.md` define la verdad del producto; este documento define cómo se presenta. No inicializar otra identidad para el micrositio: extender la aquí descrita.

## Dirección

**B2B mayorista honesto y operable.** La interfaz debe parecer una herramienta de compra y venta por bultos, no una tienda B2C ni una plantilla de tarjetas genéricas. El precio por bulto, el MOQ y el stock son la jerarquía dominante. Las acciones comerciales son cotizar y publicar; no existen carrito, checkout ni promesas de envío.

El micrositio del Empresario es una **vitrina con membrete comercial**: identidad de la empresa arriba, datos de compra en una banda continua y catálogo debajo. El color elegido por el Empresario es un recurso de marca, no una licencia para perder contraste.

## Tokens

Definidos en `resources/css/app.css` y `resources/css/microsite.css`.

- Marca Mayoro: escala `brand` azul (`#12305c` como referencia principal); `accent` ámbar para acciones globales de publicar/cotizar.
- Micrositio: `--ms-brand` y `--ms-brand-ink` se imprimen en el wrapper desde `color_primario` y `color_primario_texto` del modelo `User`.
- Tinta del micrositio: `--ms-ink: #172033`, `--ms-muted: #5b6474`, `--ms-line: #e2e5ea`, `--ms-paper: #ffffff`.
- Radios: membrete 20px (18px en móvil), tarjetas 18px, botones y buscador 12px.
- Sombras con desplazamiento vertical y desenfoque amplio; no halos de color sin offset.

## Membrete del micrositio

- Fondo `--ms-brand`, texto `--ms-brand-ink`, trama diagonal al 5% y monograma grande al 7% como marca de agua.
- Monograma de dos letras derivado de la razón social, sin inventar logotipos. Se omiten conectores, sufijos societarios y tokens numéricos.
- H1 con `clamp(2rem, 4.6vw, 3.4rem)`, tracking `-0.035em`, medida máxima 20ch y `text-wrap: balance`.
- WhatsApp es la acción principal (`#25d366` con tinta `#07301c`). “Editar mi empresa” solo aparece al dueño.
- RUC, horario, dirección y catálogo activo forman una banda de cuatro columnas con divisores; dos columnas hasta 1023px y una en móvil. No se usan cuatro tarjetas independientes dentro del hero.

## Tarjetas de catálogo

- Rejilla de 3 columnas en escritorio, 2 hasta 1023px y 1 en móvil.
- Medios con proporción 16:10. Si no hay fotografía, se muestra un monograma del producto sobre un tinte de marca al 5%, nunca un rectángulo gris con “Sin imagen”.
- El monograma usa `color-mix(in srgb, var(--ms-brand) 60%, #172033)` para conservar contraste también con marcas claras.
- Orden del contenido: nombre, presentación/unidades, precio por bulto grande, unitario referencial sin IGV, tramo por volumen si existe, línea MOQ/stock y una sola acción.
- “Cotizar pedido” usa el color de la empresa con la tinta calculada; producto agotado usa botón deshabilitado y medios en escala de grises.
- Hover: elevación de 3px, borde teñido y zoom de imagen 1.03. Con `prefers-reduced-motion` no hay desplazamiento ni transición.

## Editor del micrositio

- Formulario a la izquierda y vista previa del membrete a la derecha, fija en escritorio (`lg:sticky`).
- El editor reutiliza el mismo lenguaje visual del micrositio público; no es una maqueta distinta.
- Paletas rápidas accesibles por botón con nombre y valor hexadecimal. El script actualiza color, tinta de contraste, vista previa y valor hexadecimal visible.
- Los campos mantienen sus `data-testid` y la propiedad se valida en servidor; el rediseño no cambia permisos.

## Reglas transversales

- No añadir *eyebrows* sobre encabezados en el micrositio: primero el título, después la explicación.
- Texto secundario como mínimo `#5b6474` sobre blanco; en superficies de marca se usa la tinta calculada a opacidad completa, no blanco semitransparente.
- Controles de al menos 44px de alto, foco visible y objetivos táctiles separados.
- Nada de animaciones por scroll, parallax, carruseles, gradientes violetas ni efectos decorativos ajenos a una vitrina comercial.
- No fabricar fotografías, logotipos, reseñas, cifras de clientes ni insignias. Los monogramas son la alternativa honesta a los activos ausentes.
