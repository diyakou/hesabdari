<?php

namespace App\Models;

use App\Enums\PartyRoleType;
use App\Enums\PartyType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'mobile',
        'phone',
        'national_id',
        'address',
        'credit_limit_rials',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'type' => PartyType::class,
        'credit_limit_rials' => 'integer',
        'is_active' => 'boolean',
    ];

    public function roles(): HasMany
    {
        return $this->hasMany(PartyRole::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    public function hasRole(PartyRoleType|string $role): bool
    {
        $roleValue = $role instanceof PartyRoleType ? $role->value : $role;

        return $this->roles->contains('role', $roleValue);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(PartyRoleType::Customer);
    }

    public function isSupplier(): bool
    {
        return $this->hasRole(PartyRoleType::Supplier);
    }
}
