<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Mayoro: plataforma B2B que conecta bodegas con distribuidores mayoristas. Catálogo con precios por bulto, MOQ y cotizacion directa por WhatsApp.">
    <title>Mayoro &mdash; Conexion Mayorista B2B</title>
    <style>
        :root {
            color-scheme: light;
            --brand: #12305c;
            --brand-light: #1e4d8f;
            --accent: #f59e0b;
            --ink: #1f2937;
            --muted: #6b7280;
            --line: #e5e7eb;
            --bg: #f5f6f8;
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: var(--ink); }
        a { color: var(--brand-light); }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem; }

        .topnav { background: var(--brand); color: #fff; display: flex; gap: 1rem; padding: .85rem 1.5rem; align-items: center; flex-wrap: wrap; }
        .topnav a { color: #fff; text-decoration: none; font-weight: 500; }
        .topnav a:hover { text-decoration: underline; }
        .topnav .brand { font-weight: 700; font-size: 1.2rem; margin-right: 1.5rem; }
        .topnav .grow { flex: 1; }
        .btn { display: inline-block; padding: .55rem 1rem; border-radius: .375rem; background: var(--brand-light); color: #fff !important; text-decoration: none; border: 0; cursor: pointer; font-size: .9rem; font-weight: 600; }
        .btn:hover { background: #163a6b; }
        .btn.ghost { background: transparent; border: 1px solid rgba(255,255,255,.5); }
        .btn.accent { background: var(--accent); color: #3b2500 !important; }
        .btn.accent:hover { background: #d98c07; }

        .hero { background: linear-gradient(135deg, #12305c 0%, #1e4d8f 100%); color: #fff; padding: 3.5rem 0; }
        .hero h1 { font-size: 2.4rem; margin: 0 0 .75rem; line-height: 1.2; }
        .hero p { font-size: 1.1rem; margin: 0 0 1.5rem; color: #d7e3f5; max-width: 55ch; }
        .hero .actions { display: flex; gap: .75rem; flex-wrap: wrap; }

        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-top: 2.5rem; }
        .stat { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22); border-radius: .5rem; padding: 1.1rem 1.25rem; }
        .stat .value { font-size: 1.8rem; font-weight: 700; }
        .stat .label { font-size: .85rem; color: #c9d8ef; }

        section { margin: 3rem 0; }
        section h2 { font-size: 1.5rem; margin: 0 0 .4rem; }
        section .lead { color: var(--muted); margin: 0 0 1.5rem; font-size: .95rem; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: .5rem; padding: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .card h3 { margin: 0 0 .4rem; font-size: 1.05rem; }
        .card p { margin: 0; font-size: .9rem; color: var(--muted); }
        .card .step { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 50%; background: var(--brand); color: #fff; font-weight: 700; font-size: .9rem; margin-bottom: .75rem; }

        .product { background: #fff; border: 1px solid var(--line); border-radius: .5rem; padding: 1.25rem; display: flex; flex-direction: column; gap: .5rem; }
        .product .cat { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); }
        .product .name { font-weight: 700; font-size: 1rem; }
        .product .price { font-size: 1.3rem; font-weight: 700; color: var(--brand); }
        .product .price small { font-size: .75rem; font-weight: 500; color: var(--muted); display: block; }
        .product .meta { font-size: .82rem; color: var(--muted); }
        .tag { display: inline-block; font-size: .72rem; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; border-radius: 999px; padding: .1rem .55rem; }

        .cta { background: var(--brand); color: #fff; border-radius: .75rem; padding: 2.5rem; text-align: center; }
        .cta h2 { color: #fff; }
        .cta p { color: #d7e3f5; }

        footer { background: #0d2347; color: #a9bdd9; padding: 2rem 0; font-size: .85rem; margin-top: 3rem; }
        footer .brand { color: #fff; font-weight: 700; font-size: 1.1rem; }
        footer a { color: #cfe0f5; }

        @media (max-width: 640px) {
            .hero h1 { font-size: 1.8rem; }
            .topnav { gap: .75rem; }
        }
    </style>
</head>
<body>

<nav class="topnav">
    <span class="brand">Mayoro</span>
    <a href="{{ route('home') }}">Inicio</a>
    @auth
        <a href="{{ route('dashboard.index') }}">Dashboard</a>
        <a href="{{ route('products.index') }}">Productos</a>
        <span class="grow"></span>
        <form method="POST" action="{{ route('auth.logout') }}">
            @csrf
            <button class="btn ghost" type="submit">Cerrar sesion</button>
        </form>
    @endauth
    @guest
        <span class="grow"></span>
        <a href="{{ route('auth.login') }}">Ingresar</a>
        <a class="btn accent" href="{{ route('auth.register') }}">Registrarse</a>
    @endguest
</nav>

<header class="hero">
    <div class="wrap">
        <h1>Conecta tu bodega con distribuidores mayoristas</h1>
        <p>
            Mayoro es la plataforma B2B donde los comercios publican sus productos con
            precio por bulto y pedido minimo, y las bodegas cotizan directo con el
            distribuidor por WhatsApp, sin llamadas intermedias.
        </p>
        <div class="actions">
            @guest
                <a class="btn accent" href="{{ route('auth.register') }}">Crear cuenta de comercio</a>
                <a class="btn ghost" href="{{ route('auth.login') }}">Ya tengo cuenta</a>
            @endauth
            <a class="btn ghost" href="{{ route('products.index') }}">Ver catalogo</a>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="value">{{ $metricas['productos'] }}</div>
                <div class="label">Productos publicados</div>
            </div>
            <div class="stat">
                <div class="value">{{ $metricas['distribuidores'] }}</div>
                <div class="label">Distribuidores mayoristas</div>
            </div>
            <div class="stat">
                <div class="value">{{ $metricas['bodegas'] }}</div>
                <div class="label">Bodegas conectadas</div>
            </div>
        </div>
    </div>
</header>

<main class="wrap">

    <section>
        <h2>Productos destacados</h2>
        <p class="lead">Ultimas publicaciones activas del catalogo mayorista.</p>

        @if ($destacados->isEmpty())
            <div class="card">
                <h3>El catalogo aun esta vacio</h3>
                <p>
                    Registrate como distribuidor y publica tu primer producto con precio por
                    bulto y pedido minimo para que las bodegas lo vean.
                </p>
                <p style="margin-top:1rem">
                    <a class="btn" href="{{ route('auth.register') }}">Publicar mi primer producto</a>
                </p>
            </div>
        @else
            <div class="grid">
                @foreach ($destacados as $producto)
                    <article class="product">
                        <span class="cat">{{ $producto->categoria }}</span>
                        <span class="name">{{ $producto->nombre }}</span>
                        <span class="price">
                            S/ {{ number_format((float) $producto->precio_bulto, 2) }}
                            <small>{{ $producto->unidades_por_bulto }} und. por bulto &middot; S/ {{ number_format($producto->precio_unitario_calculado, 2) }} c/u</small>
                        </span>
                        <span class="tag">Pedido minimo: {{ $producto->moq_cantidad_minima }} bultos</span>
                        <span class="meta">{{ $producto->presentacion }}</span>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <h2>Como funciona Mayoro</h2>
        <p class="lead">Tres pasos para cerrar una compra mayorista.</p>
        <div class="grid">
            <div class="card">
                <span class="step">1</span>
                <h3>Registra tu comercio</h3>
                <p>Alta con RUC, WhatsApp y rol: bodega o distribuidor.</p>
            </div>
            <div class="card">
                <span class="step">2</span>
                <h3>Publica o explora precios</h3>
                <p>El distribuidor define precio por bulto, unidades y MOQ. La bodega compara el catalogo.</p>
            </div>
            <div class="card">
                <span class="step">3</span>
                <h3>Cotiza por WhatsApp</h3>
                <p>El sistema valida el MOQ y genera el resumen listo para enviar al distribuidor.</p>
            </div>
        </div>
    </section>

    <section class="cta">
        <h2>Empieza a mover mayorista</h2>
        <p>Registro gratuito para bodegas y distribuidores. Solo necesitas tu RUC y tu numero de WhatsApp.</p>
        <p>
            <a class="btn accent" href="{{ route('auth.register') }}">Registrar mi comercio</a>
        </p>
    </section>

</main>

<footer>
    <div class="wrap">
        <div class="brand">Mayoro</div>
        <p>Plataforma B2B de conexion mayorista para bodegas y distribuidores.</p>
        <p>Proyecto academico &mdash; Pruebas y Calidad de Software &middot; Universidad Continental.</p>
    </div>
</footer>

</body>
</html>
