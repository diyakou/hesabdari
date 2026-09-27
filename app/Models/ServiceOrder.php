<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'party_id',
        'service_definition_id',
        'service_form_version_id',
        'form_data',
        'status',
        'direct_cost_rials',
        'promised_date',
        'delivered_date',
        'technician_id',
        'invoice_id',
        'notes',
        'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'form_data' => 'array',
        'promised_date' => 'date',
        'delivered_date' => 'datetime',
        'direct_cost_rials' => 'integer',
    ];

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function serviceDefinition(): BelongsTo
    {
        return $this->belongsTo(ServiceDefinition::class);
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(ServiceFormVersion::class, 'service_form_version_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'queued' => 'در صف انتظار',
            'in_progress' => 'در حال انجام',
            'ready' => 'آماده تحویل',
            'delivered' => 'تحویل به مشتری',
            'cancelled' => 'لغو شده',
            default => $this->status,
        };
    }
}
