<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_definitions', function (Blueprint $table): void {
            $table->string('category', 30)->default('software')->after('code')->index();
        });
    }

    public function down(): void
    {
        Schema::table('service_definitions', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
