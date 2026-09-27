<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'invoice_number',
        'party_id',
        'warehouse_id',
        'issue_date',
        'status',
        'subtotal_rials',
        'discount_rials',
        'additional_cost_rials',
        'total_amount_rials',
        'idempotency_key',
        'notes',
        'created_by',
        'reference_invoice_id',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'subtotal_rials' => 'integer',
        'discount_rials' => 'integer',
        'additional_cost_rials' => 'integer',
        'total_amount_rials' => 'integer',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referenceInvoice(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reference_invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function purchasePaymentPlan(): HasOne
    {
        return $this->hasOne(PurchasePaymentPlan::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPurchase(): bool
    {
        return $this->type === 'purchase';
    }

    public function isSale(): bool
    {
        return $this->type === 'sale';
    }

    public function isProforma(): bool
    {
        return $this->type === 'proforma';
    }

    public function isSaleReturn(): bool
    {
        return $this->type === 'sale_return';
    }

    public function isPurchaseReturn(): bool
    {
        return $this->type === 'purchase_return';
    }
}
