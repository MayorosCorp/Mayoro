<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasFactory;

    protected $table = 'cotizaciones_b2b';

    protected $fillable = [
        'producto_id',
        'bodega_id',
        'distribuidor_id',
        'cantidad_solicitada',
        'precio_unitario',
        'subtotal',
        'igv',
        'total',
        'telefono_destino',
        'url_whatsapp',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_solicitada' => 'integer',
            'precio_unitario' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'igv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    /**
     * Producto mayorista cotizado.
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    /**
     * Comercio bodeguero que genera la solicitud.
     */
    public function bodega(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bodega_id');
    }

    /**
     * Comercio distribuidor que recibe la cotización.
     */
    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_id');
    }
}
