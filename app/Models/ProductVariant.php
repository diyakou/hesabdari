<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'color',
        'storage',
        'ram',
        'selling_price_rials',
        'min_selling_price_rials',
    ];

    protected $casts = [
        'selling_price_rials' => 'integer',
        'min_selling_price_rials' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([
            $this->product?->name,
            $this->color,
            $this->storage,
            $this->ram ? "RAM {$this->ram}" : null,
        ]);

        return implode(' - ', $parts);
    }
}
