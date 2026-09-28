<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'payment_number',
        'party_id',
        'financial_account_id',
        'amount_rials',
        'payment_method',
        'reference_number',
        'date',
        'idempotency_key',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_rials' => 'integer',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }
}
