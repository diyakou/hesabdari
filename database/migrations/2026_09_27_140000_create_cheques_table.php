<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cheques', function (Blueprint $table): void {
            $table->id();
            $table->string('direction', 20); // received, issued
            $table->string('status', 30)->default('on_hand'); // on_hand, scheduled, endorsed, cleared, bounced, void
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('source_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('endorsed_to_party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->foreignId('endorsed_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('check_number', 100);
            $table->string('sayad_id', 16)->nullable()->unique();
            $table->string('bank_name', 100);
            $table->string('account_owner', 150)->nullable();
            $table->unsignedBigInteger('amount_rials');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cheques');
    }
};
