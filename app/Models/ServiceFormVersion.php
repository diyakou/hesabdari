<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceFormVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_definition_id',
        'version',
        'fields_schema',
    ];

    protected $casts = [
        'version' => 'integer',
        'fields_schema' => 'array',
    ];

    public function serviceDefinition(): BelongsTo
    {
        return $this->belongsTo(ServiceDefinition::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
