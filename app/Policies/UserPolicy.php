<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isManager();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isManager();
    }

    public function create(User $actor): bool
    {
        return $actor->isManager();
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->isManager();
    }

    public function delete(User $actor, User $user): bool
    {
        return false;
    }
}
