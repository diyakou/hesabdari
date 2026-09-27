<?php

namespace App\Actions\Services;

use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TransitionServiceOrderStatusAction
{
    private const ALLOWED_TRANSITIONS = [
        'queued' => ['in_progress', 'cancelled'],
        'in_progress' => ['ready', 'cancelled'],
        'ready' => ['delivered', 'cancelled'],
        'delivered' => [], // Terminal state
        'cancelled' => [], // Terminal state
    ];

    public function execute(ServiceOrder $order, string $newStatus, ?string $reason = null, ?User $actor = null): ServiceOrder
    {
        $currentStatus = $order->status;

        $allowed = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            $fromLabel = $order->statusLabel();
            throw ValidationException::withMessages([
                'status' => "تغییر وضعیت از «{$fromLabel}» به «{$newStatus}» مجاز نمی‌باشد.",
            ]);
        }

        if ($newStatus === 'cancelled' && empty($reason)) {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'برای لغو سفارش خدمت، ثبت دلیل لغو الزامی است.',
            ]);
        }

        $order->status = $newStatus;

        if ($newStatus === 'delivered') {
            $order->delivered_date = now();
        }

        if ($newStatus === 'cancelled') {
            $order->cancellation_reason = $reason;
        }

        $order->save();

        return $order;
    }
}
