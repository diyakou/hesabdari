<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceDefinition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'category',
        'description',
        'default_fee_rials',
        'is_active',
    ];

    protected $casts = [
        'default_fee_rials' => 'integer',
        'is_active' => 'boolean',
    ];

    public function formVersions(): HasMany
    {
        return $this->hasMany(ServiceFormVersion::class);
    }

    public function latestFormVersion(): HasOne
    {
        return $this->hasOne(ServiceFormVersion::class)->latestOfMany('version');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }
}
