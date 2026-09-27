<?php

namespace App\Models;

use App\Enums\ProductType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'brand_id',
        'category_id',
        'is_active',
    ];

    protected $casts = [
        'type' => ProductType::class,
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function isSerialized(): bool
    {
        return $this->type === ProductType::Serialized;
    }

    public function isStock(): bool
    {
        return $this->type === ProductType::Stock;
    }

    public function isService(): bool
    {
        return $this->type === ProductType::Service;
    }
}
