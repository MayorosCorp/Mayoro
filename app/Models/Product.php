<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $table = 'productos_mayoristas';

    protected $fillable = [
        'distribuidor_id',
        'nombre',
        'descripcion',
        'categoria',
        'presentacion',
        'unidades_por_bulto',
        'precio_bulto',
        'moq_cantidad_minima',
        'precio_unitario_sugerido',
        'stock_disponible',
        'imagen_url',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'precio_bulto' => 'decimal:2',
            'precio_unitario_sugerido' => 'decimal:2',
            'stock_disponible' => 'integer',
            'unidades_por_bulto' => 'integer',
            'moq_cantidad_minima' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relación con el comercio distribuidor mayorista propietario del producto.
     */
    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_id');
    }

    /**
     * Calcula dinámicamente el precio unitario sugerido: precio_bulto / unidades_por_bulto.
     */
    public function getPrecioUnitarioCalculadoAttribute(): float
    {
        if ($this->unidades_por_bulto > 0) {
            return round((float) $this->precio_bulto / (int) $this->unidades_por_bulto, 2);
        }

        return 0.00;
    }

    /**
     * Un producto solo es comprable si está activo y además tiene existencias (HU-03).
     */
    public function getEstaAgotadoAttribute(): bool
    {
        return ! $this->is_active || (int) $this->stock_disponible <= 0;
    }
}
