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
        Schema::create('loan_product_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_version_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['loan_product_version_id', 'purpose'], 'product_account_mapping_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_product_account_mappings');
    }
};
