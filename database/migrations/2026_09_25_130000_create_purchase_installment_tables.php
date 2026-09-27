<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->unique()->constrained('invoices')->cascadeOnDelete();
            $table->string('payment_type', 20)->default('cash');
            $table->unsignedBigInteger('down_payment_rials')->default(0);
            $table->unsignedSmallInteger('installments_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_payment_plan_id')->constrained()->cascadeOnDelete();
            $table->string('check_number', 100);
            $table->string('sayad_id', 16)->nullable()->index();
            $table->string('bank_name', 100);
            $table->string('account_owner', 150)->nullable();
            $table->unsignedBigInteger('amount_rials');
            $table->date('due_date');
            $table->string('status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_checks');
        Schema::dropIfExists('purchase_payment_plans');
    }
};
