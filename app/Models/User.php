<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isManager(): bool
    {
        return $this->is_active && $this->role === UserRole::Manager;
    }

    public function isAccountant(): bool
    {
        return $this->is_active && $this->role === UserRole::Accountant;
    }

    public function isSalesperson(): bool
    {
        return $this->is_active && $this->role === UserRole::Salesperson;
    }

    public function isWarehouseKeeper(): bool
    {
        return $this->is_active && $this->role === UserRole::WarehouseKeeper;
    }

    public function canSeeFinancials(): bool
    {
        return $this->is_active && ($this->role === UserRole::Manager || $this->role === UserRole::Accountant);
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }
}
