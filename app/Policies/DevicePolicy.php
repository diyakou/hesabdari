<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Device $device): bool
    {
        return $actor->is_active;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ! $actor->isSalesperson();
    }

    public function update(User $actor, Device $device): bool
    {
        return $actor->is_active && ($actor->isManager() || $actor->isWarehouseKeeper());
    }

    public function delete(User $actor, Device $device): bool
    {
        return false;
    }
}
