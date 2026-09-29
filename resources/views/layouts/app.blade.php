<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mayoro') &mdash; Mayoro</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background: #f5f6f8; color: #1f2937; }
        .topnav { background: #12305c; color: #fff; display: flex; gap: 1rem; padding: .8rem 1.5rem; align-items: center; flex-wrap: wrap; }
        .topnav a { color: #fff; text-decoration: none; font-weight: 500; }
        .topnav a:hover { text-decoration: underline; }
        .topnav .brand { font-weight: 700; font-size: 1.15rem; margin-right: 1.5rem; }
        .topnav .grow { flex: 1; }
        .container { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: 1.5rem; box-shadow: 0 1px 2px rgba(0,0,0,.05); }
        .btn { display: inline-block; padding: .5rem .9rem; border-radius: .375rem; background: #2563eb; color: #fff !important; text-decoration: none; border: 0; cursor: pointer; font-size: .9rem; }
        .btn:hover { background: #1d4ed8; }
        .btn.ghost { background: transparent; color: #1f2937; border: 1px solid #d1d5db; }
        .btn[disabled], .btn.disabled { background: #9ca3af; cursor: not-allowed; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { text-align: left; padding: .6rem .75rem; border-bottom: 1px solid #e5e7eb; font-size: .9rem; }
        th { background: #f9fafb; }
        label { display: block; font-size: .85rem; font-weight: 600; margin: .9rem 0 .3rem; }
        input, select, textarea { width: 100%; padding: .5rem .6rem; border: 1px solid #d1d5db; border-radius: .375rem; font-size: .95rem; }
        .flash { padding: .75rem 1rem; border-radius: .375rem; margin-bottom: 1rem; }
        .flash.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .flash.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .error-list li { color: #b91c1c; font-size: .85rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .stat { background: #fff; border: 1px solid #e5e7eb; border-radius: .5rem; padding: 1.25rem; }
        .stat .value { font-size: 1.6rem; font-weight: 700; }
        .stat .label { color: #6b7280; font-size: .85rem; }
    </style>
</head>
<body>
    <nav class="topnav">
        <span class="brand">Mayoro</span>
        @auth
            <a href="{{ route('dashboard.index') }}">Dashboard</a>
            <a href="{{ route('products.index') }}">Productos</a>
            <a href="{{ route('suppliers.index') }}">Distribuidores</a>
            <a href="{{ route('quotes.index') }}">Cotizaciones</a>
            <a href="{{ route('orders.index') }}">Pedidos</a>
            <a href="{{ route('inventory.index') }}">Inventario</a>
            <a href="{{ route('reports.index') }}">Reportes</a>
            @if (auth()->user()->rol === 'distribuidor')
                <a class="btn" style="background:#f59e0b; color:#3b2500 !important; padding:.3rem .7rem;"
                   href="{{ route('products.create') }}">+ Publicar Producto</a>
            @endif
            <span class="grow"></span>
            <span style="font-size:.85rem; opacity:.85;">{{ auth()->user()->razon_social }}</span>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button class="btn ghost" type="submit">Cerrar sesión</button>
            </form>
        @endauth
        @guest
            <a href="{{ route('home') }}">Inicio</a>
            <a href="{{ route('products.index') }}">Catálogo</a>
            <a href="{{ route('suppliers.index') }}">Distribuidores</a>
            <span class="grow"></span>
            <a href="{{ route('auth.login') }}">Ingresar</a>
            <a class="btn" style="background:#f59e0b; color:#3b2500 !important;" href="{{ route('auth.register') }}">Registrarse</a>
        @endguest
    </nav>

    <main class="container">
        @if (session('status') || session('success'))
            <div class="flash success">{{ session('status') ?? session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="flash error">
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
