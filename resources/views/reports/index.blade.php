@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
    <section class="mx-auto max-w-2xl">
        <p class="section-eyebrow">Módulo en preparación</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Reportes</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            La generación de reportes todavía no está disponible. Mientras tanto, el dashboard
            resume el catálogo y las cotizaciones de tu cuenta.
        </p>

        <div class="card mt-6">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">Datos disponibles hoy</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                El panel de trabajo muestra productos publicados, cotizaciones generadas,
                distribuidores registrados y productos que hoy no se pueden cotizar.
            </p>

            <div class="mt-5 flex flex-wrap gap-3">
                <a class="btn" href="{{ route('dashboard.index') }}">Ver dashboard</a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Mis cotizaciones</a>
            </div>
        </div>
    </section>
@endsection
