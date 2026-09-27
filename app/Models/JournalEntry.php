<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_number',
        'date',
        'source_type',
        'source_id',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function getTotalDebitAttribute(): int
    {
        return (int) $this->lines->sum('debit_rials');
    }

    public function getTotalCreditAttribute(): int
    {
        return (int) $this->lines->sum('credit_rials');
    }

    public function isBalanced(): bool
    {
        return $this->total_debit === $this->total_credit;
    }
}
