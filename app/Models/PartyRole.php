<?php

namespace App\Models;

use App\Enums\PartyRoleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'party_id',
        'role',
    ];

    protected $casts = [
        'role' => PartyRoleType::class,
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
