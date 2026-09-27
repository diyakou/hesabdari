<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoices (Purchase, Sale, Proforma, Returns)
        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30); // purchase, sale, proforma, sale_return, purchase_return
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('issue_date');
            $table->string('status', 30)->default('draft'); // draft, finalized, cancelled
            $table->unsignedBigInteger('subtotal_rials')->default(0);
            $table->unsignedBigInteger('discount_rials')->default(0);
            $table->unsignedBigInteger('additional_cost_rials')->default(0); // Landed cost for purchases
            $table->unsignedBigInteger('total_amount_rials')->default(0);
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('reference_invoice_id')->nullable()->constrained('invoices')->nullOnDelete(); // for returns
            $table->timestamps();

            $table->index(['type', 'status', 'issue_date']);
        });

        // Invoice Lines
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('device_id')->nullable()->constrained('devices')->nullOnDelete();
            $table->integer('quantity'); // strictly 1 for serialized devices
            $table->unsignedBigInteger('unit_price_rials');
            $table->unsignedBigInteger('discount_rials')->default(0);
            $table->unsignedBigInteger('allocated_cost_rials')->default(0); // Allocated landed cost
            $table->unsignedBigInteger('snapshot_cost_rials')->default(0); // Unit cost at the moment of exit or purchase
            $table->foreignId('reference_line_id')->nullable()->constrained('invoice_lines')->nullOnDelete(); // For returns
            $table->timestamps();
        });

        // Payments (Receipt from customer, Payment to supplier)
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30); // receipt, payment, refund
            $table->string('payment_number', 50)->unique();
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('financial_account_id')->constrained('financial_accounts');
            $table->unsignedBigInteger('amount_rials');
            $table->string('payment_method', 30)->default('cash'); // cash, bank_transfer, pos
            $table->string('reference_number', 100)->nullable();
            $table->date('date');
            $table->string('idempotency_key', 100)->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['party_id', 'type', 'date']);
        });

        // Payment Allocations (Linking payments to invoices)
        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_rials');
            $table->timestamps();

            $table->unique(['payment_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
    }
};
