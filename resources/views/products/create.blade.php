@extends('layouts.app')

@section('title', 'Publicar Producto Mayorista - Mayoro B2B')

@section('content')
    <div style="max-width:750px; margin:0 auto;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h1>Publicar Producto Mayorista</h1>
            <a class="btn ghost" href="{{ route('products.index') }}">← Volver al catálogo</a>
        </div>

        @if (session('success'))
            <div style="background:#dcfce7; border:1px solid #22c55e; color:#15803d; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1.5rem;">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div style="background:#fee2e2; border:1px solid #ef4444; color:#b91c1c; padding:0.75rem 1rem; border-radius:6px; margin-bottom:1.5rem;">
                <p style="font-weight:bold; margin-bottom:0.5rem;">Por favor corrige los siguientes errores:</p>
                <ul style="margin:0; padding-left:1.2rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                @csrf

                <div style="display:grid; grid-template-columns: 2fr 1fr; gap:1rem;">
                    <div>
                        <label for="nombre">Nombre Comercial del Producto</label>
                        <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" placeholder="Ej. Aceite Primor 1L x 12" required>
                    </div>
                    <div>
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria" required style="width:100%; padding:0.55rem; border:1px solid #ccc; border-radius:4px;">
                            <option value="">Selecciona...</option>
                            <option value="Abarrotes" {{ old('categoria') === 'Abarrotes' ? 'selected' : '' }}>Abarrotes</option>
                            <option value="Bebidas" {{ old('categoria') === 'Bebidas' ? 'selected' : '' }}>Bebidas</option>
                            <option value="Lácteos" {{ old('categoria') === 'Lácteos' ? 'selected' : '' }}>Lácteos</option>
                            <option value="Limpieza" {{ old('categoria') === 'Limpieza' ? 'selected' : '' }}>Limpieza</option>
                            <option value="Golosinas" {{ old('categoria') === 'Golosinas' ? 'selected' : '' }}>Golosinas</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:1rem;">
                    <div>
                        <label for="presentacion">Presentación de Empaque</label>
                        <input id="presentacion" type="text" name="presentacion" value="{{ old('presentacion') }}" placeholder="Ej. Caja, Fardo, Saco, Display" required>
                    </div>
                    <div>
                        <label for="unidades_por_bulto">Unidades por Empaque</label>
                        <input id="unidades_por_bulto" type="number" min="1" step="1" name="unidades_por_bulto" value="{{ old('unidades_por_bulto', 1) }}" required>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; margin-top:1rem;">
                    <div>
                        <label for="precio_bulto">Precio por Bulto / Empaque (S/.)</label>
                        <input id="precio_bulto" type="number" step="0.01" min="0.01" name="precio_bulto" value="{{ old('precio_bulto') }}" placeholder="0.00" required>
                    </div>
                    <div>
                        <label for="moq_cantidad_minima">Pedido Mínimo Mayorista (MOQ)</label>
                        <input id="moq_cantidad_minima" type="number" min="1" step="1" name="moq_cantidad_minima" value="{{ old('moq_cantidad_minima', 1) }}" placeholder="Mínimo 1" required>
                    </div>
                </div>

                <div style="margin-top:1rem;">
                    <label for="stock_disponible">Unidades Disponibles en Stock</label>
                    <input id="stock_disponible" type="number" min="0" step="1"
                           name="stock_disponible" value="{{ old('stock_disponible', 0) }}" required>
                    <small style="display:block; color:#6b7280; font-size:.8rem; margin-top:.3rem;">
                        Si lo dejas en 0, el producto se publica igualmente pero el catálogo lo
                        mostrará como <em>"Temporalmente sin stock"</em>, sin botón de compra.
                    </small>
                </div>

                <div style="margin-top:1rem;">
                    <label for="descripcion">Descripción del Producto y Condiciones Comerciales (Opcional)</label>
                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles de conservación, fecha de vencimiento o promociones por volumen...">{{ old('descripcion') }}</textarea>
                </div>

                <div style="margin-top:1rem;">
                    <label for="imagen">Imagen del Producto (Opcional)</label>
                    <input id="imagen" type="file" name="imagen" accept="image/png,image/jpeg,image/webp"
                           style="padding:.4rem;">
                    <small style="display:block; color:#6b7280; font-size:.8rem; margin-top:.3rem;">
                        Formatos admitidos: PNG, JPG o WebP. Peso máximo 2 MB.
                        El archivo se almacena con nombre saneado y ruta generada por el servidor.
                    </small>
                </div>

                <div style="background:#f0fdf4; border:1px solid #bbf7d0; padding:0.75rem; border-radius:6px; margin-top:1rem; font-size:0.9rem; color:#166534;">
                    ℹ️ <strong>Cálculo Automático B2B:</strong> El precio unitario sugerido para la bodega se calculará automáticamente dividiendo el precio por bulto entre las unidades del empaque.
                </div>

                <div style="margin-top:1.5rem; display:flex; gap:1rem;">
                    <button class="btn" type="submit" style="flex:1;">Publicar en Catálogo Mayorista</button>
                    <a class="btn ghost" href="{{ route('products.index') }}">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection