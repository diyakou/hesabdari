<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && ($actor->isManager() || $actor->isAccountant());
    }

    public function view(User $actor, Payment $payment): bool
    {
        return $actor->is_active && ($actor->isManager() || $actor->isAccountant());
    }

    public function create(User $actor): bool
    {
        return $actor->is_active && ($actor->isManager() || $actor->isAccountant());
    }

    public function update(User $actor, Payment $payment): bool
    {
        return false; // Payments are immutable
    }

    public function delete(User $actor, Payment $payment): bool
    {
        return false;
    }
}
