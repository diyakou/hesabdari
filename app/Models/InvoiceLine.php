<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'product_variant_id',
        'device_id',
        'quantity',
        'unit_price_rials',
        'discount_rials',
        'allocated_cost_rials',
        'snapshot_cost_rials',
        'reference_line_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_rials' => 'integer',
        'discount_rials' => 'integer',
        'allocated_cost_rials' => 'integer',
        'snapshot_cost_rials' => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function referenceLine(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_line_id');
    }

    public function getNetTotalRialsAttribute(): int
    {
        return ($this->quantity * $this->unit_price_rials) - $this->discount_rials;
    }
}
