<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Chart of accounts
        Schema::create('ledger_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('type', 30); // asset, liability, equity, revenue, expense
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Financial accounts (Cash boxes, Bank accounts, POS terminals)
        Schema::create('financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 30); // cash, bank, pos
            $table->string('account_number', 50)->nullable();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Double-entry Journal Entries
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->date('date');
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description');
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });

        // 4. Journal Entry Lines
        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts');
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->unsignedBigInteger('debit_rials')->default(0);
            $table->unsignedBigInteger('credit_rials')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['party_id', 'ledger_account_id']);
        });

        // 5. Stock Balances (Quantity + Moving Weighted Average Value)
        Schema::create('stock_balances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->unsignedBigInteger('total_cost_rials')->default(0);
            $table->timestamps();

            $table->unique(['product_variant_id', 'warehouse_id']);
        });

        // 6. Inventory Movements (Append-only audit trail)
        Schema::create('inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->integer('quantity_change'); // positive for in, negative for out
            $table->unsignedBigInteger('unit_cost_rials')->default(0);
            $table->unsignedBigInteger('total_cost_rials')->default(0);
            $table->string('document_type', 100);
            $table->unsignedBigInteger('document_id');
            $table->string('reason', 255);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_variant_id', 'warehouse_id']);
        });

        // 7. Device Movements (Audit trail for serialized devices)
        Schema::create('device_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('from_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('to_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('document_type', 100)->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_movements');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('financial_accounts');
        Schema::dropIfExists('ledger_accounts');
    }
};
