<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Brand $brand): bool
    {
        return $actor->is_active;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function update(User $actor, Brand $brand): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function delete(User $actor, Brand $brand): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }
}
