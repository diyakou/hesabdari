<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('physical_condition', 30)->default('new'); // new, used
            $table->unsignedTinyInteger('battery_health')->nullable(); // 0 to 100
            $table->string('registry_status', 30)->default('unknown'); // unknown, registered, unregistered
            $table->string('ownership', 30)->default('shop'); // shop, customer
            $table->string('operational_status', 30)->default('pending_receipt'); // pending_receipt, available, sold, quarantine, returned_to_supplier
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['operational_status', 'ownership']);
        });

        Schema::create('device_identifiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('type', 30)->default('imei'); // imei, serial
            $table->string('position', 30)->default('primary'); // primary, secondary
            $table->string('value', 50)->unique(); // global uniqueness across all positions and devices
            $table->timestamps();

            $table->unique(['device_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_identifiers');
        Schema::dropIfExists('devices');
    }
};
