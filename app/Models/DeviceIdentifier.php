<?php

namespace App\Models;

use App\Enums\IdentifierType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'type',
        'position',
        'value',
    ];

    protected $casts = [
        'type' => IdentifierType::class,
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
