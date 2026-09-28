<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cheque extends Model
{
    protected $fillable = ['direction', 'status', 'party_id', 'payment_id', 'source_invoice_id', 'endorsed_to_party_id', 'endorsed_payment_id', 'check_number', 'sayad_id', 'bank_name', 'account_owner', 'amount_rials', 'due_date', 'notes'];
    protected $casts = ['amount_rials' => 'integer', 'due_date' => 'date'];
    public function party(): BelongsTo { return $this->belongsTo(Party::class); }
    public function endorsedToParty(): BelongsTo { return $this->belongsTo(Party::class, 'endorsed_to_party_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function sourceInvoice(): BelongsTo { return $this->belongsTo(Invoice::class, 'source_invoice_id'); }
}
