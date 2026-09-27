<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'total_cost_rials',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total_cost_rials' => 'integer',
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function getAverageUnitCostAttribute(): int
    {
        if ($this->quantity <= 0) {
            return 0;
        }

        return (int) floor($this->total_cost_rials / $this->quantity);
    }
}
