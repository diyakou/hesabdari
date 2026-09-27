<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active;
    }

    public function view(User $actor, Invoice $invoice): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        // Salesperson cannot view purchase invoices (purchase price privacy)
        if ($actor->isSalesperson() && $invoice->isPurchase()) {
            return false;
        }

        return true;
    }

    public function create(User $actor): bool
    {
        return $actor->is_active;
    }

    public function update(User $actor, Invoice $invoice): bool
    {
        if (! $actor->is_active) {
            return false;
        }

        if ($invoice->isFinalized()) {
            return false; // Finalized invoices are immutable!
        }

        return $actor->isManager() || $actor->isAccountant() || $actor->isWarehouseKeeper();
    }

    public function delete(User $actor, Invoice $invoice): bool
    {
        return false; // Immutable accounting records
    }
}
