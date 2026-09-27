<?php

namespace App\Models;

use App\Enums\DeviceCondition;
use App\Enums\DeviceStatus;
use App\Enums\IdentifierType;
use App\Enums\Ownership;
use App\Enums\RegistryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'physical_condition',
        'battery_health',
        'registry_status',
        'ownership',
        'operational_status',
        'notes',
    ];

    protected $casts = [
        'physical_condition' => DeviceCondition::class,
        'battery_health' => 'integer',
        'registry_status' => RegistryStatus::class,
        'ownership' => Ownership::class,
        'operational_status' => DeviceStatus::class,
    ];

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function identifiers(): HasMany
    {
        return $this->hasMany(DeviceIdentifier::class);
    }

    public function primaryIdentifier()
    {
        return $this->hasOne(DeviceIdentifier::class)->where('position', 'primary');
    }

    public function secondaryIdentifier()
    {
        return $this->hasOne(DeviceIdentifier::class)->where('position', 'secondary');
    }

    public function getPrimaryImeiAttribute(): ?string
    {
        return $this->identifiers->firstWhere('position', 'primary')?->value;
    }

    public function getSecondaryImeiAttribute(): ?string
    {
        return $this->identifiers->firstWhere('position', 'secondary')?->value;
    }
}
