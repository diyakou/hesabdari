<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Service Definitions
        Schema::create('service_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('default_fee_rials')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Service Form Versions (schema snapshot)
        Schema::create('service_form_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_definition_id')->constrained('service_definitions')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('fields_schema');
            $table->timestamps();

            $table->unique(['service_definition_id', 'version']);
        });

        // 3. Service Orders
        Schema::create('service_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('party_id')->constrained('parties');
            $table->foreignId('service_definition_id')->constrained('service_definitions');
            $table->foreignId('service_form_version_id')->constrained('service_form_versions');
            $table->json('form_data');
            $table->string('status', 30)->default('queued'); // queued, in_progress, ready, delivered, cancelled
            $table->unsignedBigInteger('direct_cost_rials')->default(0);
            $table->date('promised_date')->nullable();
            $table->dateTime('delivered_date')->nullable();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['status', 'promised_date']);
        });

        // 4. Operating Expenses
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_number', 50)->unique();
            $table->string('category', 100);
            $table->foreignId('financial_account_id')->constrained('financial_accounts');
            $table->foreignId('party_id')->nullable()->constrained('parties')->nullOnDelete();
            $table->unsignedBigInteger('amount_rials');
            $table->date('date');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 5. Internal Fund Transfers
        Schema::create('fund_transfers', function (Blueprint $table): void {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('source_account_id')->constrained('financial_accounts');
            $table->foreignId('destination_account_id')->constrained('financial_accounts');
            $table->unsignedBigInteger('amount_rials');
            $table->date('date');
            $table->string('tracking_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('service_orders');
        Schema::dropIfExists('service_form_versions');
        Schema::dropIfExists('service_definitions');
    }
};
