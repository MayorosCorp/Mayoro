<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;

class UpdateProductRequest extends StoreProductRequest
{
    public function authorize(): bool
    {
        $product = Product::query()->findOrFail($this->route('product'));

        return $product->distribuidor_id === $this->user()?->id;
    }
}
