<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Category $category): bool
    {
        return $actor->is_active;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function delete(User $actor, Category $category): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }
}
