@extends('layouts.app')

@section('title', 'Editar producto')

@section('content')
    <a href="{{ route('products.index') }}" style="font-size:.9rem;">&larr; Volver al catálogo</a>

    <h1 style="margin-top:.75rem;">Editar producto</h1>
    <p style="color:#6b7280; margin:.25rem 0 1.5rem 0; font-size:.95rem;">
        {{ $product->nombre }} &middot; publicado por {{ $product->distribuidor->razon_social ?? 'Distribuidor' }}
    </p>

    <div class="card" style="max-width:640px;">
        <form method="POST" action="{{ route('products.update', $product->id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <label for="nombre">Nombre</label>
            <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $product->nombre) }}" required>

            <label for="categoria">Categoría</label>
            <input id="categoria" type="text" name="categoria" value="{{ old('categoria', $product->categoria) }}" required>

            <label for="presentacion">Presentación</label>
            <input id="presentacion" type="text" name="presentacion" value="{{ old('presentacion', $product->presentacion) }}" required>

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="4">{{ old('descripcion', $product->descripcion) }}</textarea>

            <label for="unidades_por_bulto">Unidades por bulto</label>
            <input id="unidades_por_bulto" type="number" min="1" step="1" name="unidades_por_bulto"
                   value="{{ old('unidades_por_bulto', $product->unidades_por_bulto) }}" required>

            <label for="precio_bulto">Precio por bulto (S/)</label>
            <input id="precio_bulto" type="number" min="0.01" step="0.01" name="precio_bulto"
                   value="{{ old('precio_bulto', $product->precio_bulto) }}" required>

            <label for="moq_cantidad_minima">Pedido mínimo (MOQ)</label>
            <input id="moq_cantidad_minima" type="number" min="1" step="1" name="moq_cantidad_minima"
                   value="{{ old('moq_cantidad_minima', $product->moq_cantidad_minima) }}" required>

            <label for="stock_disponible">Stock disponible (bultos)</label>
            <input id="stock_disponible" type="number" min="0" step="1" name="stock_disponible"
                   value="{{ old('stock_disponible', $product->stock_disponible) }}" required>
            <small style="display:block; color:#6b7280; font-size:.8rem; margin-top:.3rem;">
                Si es 0, el producto aparece en el catálogo como "Temporalmente sin stock" (HU-03).
            </small>

            <label for="imagen">Imagen del producto</label>
            @if ($product->imagen_url)
                <div style="margin-bottom:.5rem;">
                    <img src="{{ Storage::disk('public')->url($product->imagen_url) }}"
                         alt="{{ $product->nombre }}" style="max-width:180px; border-radius:6px; border:1px solid #e5e7eb;">
                    <p style="font-size:.78rem; color:#6b7280; margin:.3rem 0 0 0;">Imagen actual: {{ $product->imagen_url }}</p>
                </div>
            @endif
            <input id="imagen" type="file" name="imagen" accept="image/png,image/jpeg,image/webp"
                   style="padding:.4rem;">
            <small style="display:block; color:#6b7280; font-size:.8rem; margin-top:.3rem;">
                PNG, JPG o WebP, máximo 2 MB. Subir una imagen reemplaza la actual.
            </small>

            <div style="margin-top:1.2rem;">
                <button class="btn" type="submit">Actualizar producto</button>
                <a class="btn ghost" href="{{ route('products.index') }}">Cancelar</a>
            </div>
        </form>
    </div>

    @unless ($product->is_active)
        <div class="card" style="margin-top:1rem; border-color:#fde68a; background:#fffbeb;">
            <h2 style="margin-top:0; font-size:1.05rem;">Este producto está fuera del catálogo</h2>
            <p style="color:#92400e; margin:0 0 1rem 0; font-size:.9rem;">
                Está pausado, por lo que el catálogo público lo muestra opaco y sin botón de compra.
                Los cambios que guardes arriba se aplicarán, pero el producto no volverá a ofrecerse
                hasta que lo republiques.
            </p>

            <form method="POST" action="{{ route('products.reactivate', $product->id) }}">
                @csrf
                @method('PATCH')
                <button class="btn" type="submit">Republicar en el catálogo</button>
            </form>
        </div>
    @endunless
@endsection
