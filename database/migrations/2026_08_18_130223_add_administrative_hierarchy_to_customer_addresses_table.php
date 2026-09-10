<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('type')->constrained()->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->after('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('ward_id')->nullable()->after('district_id')->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ward_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
