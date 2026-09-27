<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Product $product): bool
    {
        return $actor->is_active;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function update(User $actor, Product $product): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function delete(User $actor, Product $product): bool
    {
        return false;
    }
}
