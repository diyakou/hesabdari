<?php

namespace App\Policies;

use App\Models\Party;
use App\Models\User;

class PartyPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Party $party): bool
    {
        return $actor->is_active;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ! $actor->isWarehouseKeeper();
    }

    public function update(User $actor, Party $party): bool
    {
        return $actor->is_active && ! $actor->isWarehouseKeeper();
    }

    public function delete(User $actor, Party $party): bool
    {
        return false;
    }
}
