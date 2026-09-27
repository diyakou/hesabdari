<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_number',
        'source_account_id',
        'destination_account_id',
        'amount_rials',
        'date',
        'tracking_number',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_rials' => 'integer',
    ];

    public function sourceAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'source_account_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
