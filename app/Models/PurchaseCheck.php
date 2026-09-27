<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseCheck extends Model
{
    protected $fillable = ['check_number', 'sayad_id', 'bank_name', 'account_owner', 'amount_rials', 'due_date', 'status', 'notes'];
    protected $casts = ['amount_rials' => 'integer', 'due_date' => 'date'];
    public function paymentPlan(): BelongsTo { return $this->belongsTo(PurchasePaymentPlan::class, 'purchase_payment_plan_id'); }
}
