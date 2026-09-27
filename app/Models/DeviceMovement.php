<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'from_status',
        'to_status',
        'from_warehouse_id',
        'to_warehouse_id',
        'document_type',
        'document_id',
        'notes',
        'created_by',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
