@extends('layouts.app')

@section('title', 'Pedidos')

@section('content')
    <section class="mx-auto max-w-2xl">
        <p class="section-eyebrow">Módulo en preparación</p>
        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Pedidos</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
            El seguimiento de pedidos todavía no está disponible. Hasta que exista, la
            operación comercial se cierra por WhatsApp a partir de la cotización.
        </p>

        <div class="card mt-6">
            <h2 class="mt-0 text-lg font-semibold text-slate-900">¿Qué puedes hacer hoy?</h2>
            <ul class="mt-2 space-y-2 text-sm leading-6 text-slate-600">
                <li>Explorar el catálogo mayorista y comparar precios por bulto.</li>
                <li>Generar una cotización y enviarla al distribuidor por WhatsApp.</li>
                <li>Revisar el historial de tus cotizaciones desde tu cuenta.</li>
            </ul>

            <div class="mt-5 flex flex-wrap gap-3">
                <a class="btn" href="{{ route('products.index') }}">Ver catálogo</a>
                <a class="btn ghost" href="{{ route('quotes.index') }}">Mis cotizaciones</a>
            </div>
        </div>
    </section>
@endsection
