<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Mayoro conecta bodegas y distribuidores mayoristas del Perú: catálogo con precio por bulto, MOQ y cotización directa por WhatsApp.">
    <title>@yield('title', 'Mayoro') &mdash; Mayoro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-brand-900 focus:shadow-lg">
        Saltar al contenido
    </a>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-brand-900 text-white shadow-md">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 px-5 py-3 sm:px-8">
            <a href="{{ route('home') }}"
               class="flex items-center gap-2 text-lg font-semibold tracking-tight text-white no-underline">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-accent-500 text-sm font-bold text-brand-950">M</span>
                Mayoro
                <span class="hidden rounded-full border border-white/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-brand-100 sm:inline">B2B</span>
            </a>

            @php
                $navBase = 'whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand-100 transition hover:bg-white/10 hover:text-white';
                $navActive = 'bg-white/10 text-white';
            @endphp

            {{--
                La navegación se queda en su propia fila y con scroll horizontal
                hasta 1280px: con siete enlaces más la acción de publicar, el
                ancho mínimo del contenido superaba el viewport y empujaba la
                página entera. Los módulos de Pedidos, Inventario y Reportes no
                se anuncian como "pronto" aquí porque todavía no existen.
            --}}
            <nav class="order-3 flex w-full min-w-0 items-center gap-1 overflow-x-auto pb-1 xl:order-none xl:w-auto xl:flex-1 xl:pb-0"
                 aria-label="Navegación principal">
                @auth
                    <a class="{{ $navBase }} {{ request()->routeIs('dashboard.*') ? $navActive : '' }}"
                       href="{{ route('dashboard.index') }}">Dashboard</a>
                    <a class="{{ $navBase }} {{ request()->routeIs('products.*') ? $navActive : '' }}"
                       href="{{ route('products.index') }}">Productos</a>
                    <a class="{{ $navBase }} {{ request()->routeIs('suppliers.*') ? $navActive : '' }}"
                       href="{{ route('suppliers.index') }}">Distribuidores</a>
                    <a class="{{ $navBase }} {{ request()->routeIs('quotes.*') ? $navActive : '' }}"
                       href="{{ route('quotes.index') }}">Cotizaciones</a>

                    @if (auth()->user()->rol === 'distribuidor')
                        <a class="ml-1 inline-flex whitespace-nowrap rounded-lg bg-accent-500 px-3 py-1.5 text-sm font-semibold text-brand-950 no-underline transition hover:bg-accent-400"
                           href="{{ route('products.create') }}">+ Publicar Producto</a>
                    @endif
                @else
                    <a class="{{ $navBase }} {{ request()->routeIs('home') ? $navActive : '' }}"
                       href="{{ route('home') }}">Inicio</a>
                    <a class="{{ $navBase }} {{ request()->routeIs('products.*') ? $navActive : '' }}"
                       href="{{ route('products.index') }}">Catálogo</a>
                    <a class="{{ $navBase }} {{ request()->routeIs('suppliers.*') ? $navActive : '' }}"
                       href="{{ route('suppliers.index') }}">Distribuidores</a>
                @endauth
            </nav>

            <div class="ml-auto flex items-center gap-3">
                @auth
                    <span class="hidden text-right text-xs leading-tight text-brand-100 sm:block">
                        <span class="block font-semibold text-white">{{ auth()->user()->razon_social }}</span>
                        <span class="capitalize">{{ auth()->user()->rol }}</span>
                    </span>
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button class="whitespace-nowrap rounded-lg border border-white/20 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/10"
                                type="submit">Cerrar sesión</button>
                    </form>
                @else
                    <a class="whitespace-nowrap rounded-lg px-2.5 py-1.5 text-sm font-medium text-brand-100 no-underline transition hover:bg-white/10 hover:text-white"
                       href="{{ route('auth.login') }}">Ingresar</a>
                    <a class="whitespace-nowrap rounded-lg bg-accent-500 px-3 py-1.5 text-sm font-semibold text-brand-950 no-underline transition hover:bg-accent-400"
                       href="{{ route('auth.register') }}">Registrarse</a>
                @endauth
            </div>
        </div>
    </header>

    <main id="contenido" class="mx-auto w-full max-w-6xl flex-1 px-5 py-8 sm:px-8">
        @if (session('status') || session('success'))
            <div class="flash success" role="status">
                <span aria-hidden="true">✓</span>
                <span>{{ session('status') ?? session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="flash error" role="alert">
                <span aria-hidden="true">!</span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="flash error" role="alert">
                <span aria-hidden="true">!</span>
                <ul class="error-list">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mt-8 border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-col gap-2 px-5 py-6 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8">
            <p class="m-0">
                <span class="font-semibold text-slate-700">Mayoro</span> conecta bodegas y distribuidores mayoristas.
            </p>
            <p class="m-0">
                La cotización se cierra por WhatsApp. Mayoro no procesa pagos ni envíos.
            </p>
        </div>
    </footer>
</body>
</html>
