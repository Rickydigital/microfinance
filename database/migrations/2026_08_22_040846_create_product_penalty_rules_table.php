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
        Schema::create('product_penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_version_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('calculation_method');
            $table->string('calculation_basis');
            $table->decimal('value', 18, 6);
            $table->unsignedInteger('grace_days')->default(0);
            $table->string('frequency')->default('Daily');
            $table->decimal('maximum_amount', 18, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['loan_product_version_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_penalty_rules');
    }
};
