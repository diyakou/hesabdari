<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchasePaymentPlan extends Model
{
    protected $fillable = ['invoice_id', 'payment_type', 'down_payment_rials', 'installments_count', 'notes'];
    protected $casts = ['down_payment_rials' => 'integer', 'installments_count' => 'integer'];
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function checks(): HasMany { return $this->hasMany(PurchaseCheck::class); }
}
