<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RN-04: tramo de precio por volumen de un producto mayorista. El umbral se
 * expresa en bultos y el precio es el del bulto desde esa cantidad.
 */
class ProductPriceTier extends Model
{
    use HasFactory;

    protected $table = 'producto_tramos_precio';

    protected $fillable = [
        'producto_id',
        'cantidad_minima',
        'precio_bulto',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_minima' => 'integer',
            'precio_bulto' => 'decimal:2',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
