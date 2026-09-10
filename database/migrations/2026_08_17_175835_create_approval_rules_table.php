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
        Schema::create('approval_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('minimum_amount', 18, 2)->default(0);
            $table->decimal('maximum_amount', 18, 2)->nullable();
            $table->string('risk_grade')->nullable();
            $table->string('role_name');
            $table->unsignedSmallInteger('sequence');
            $table->boolean('is_active')->default(true);
            $table->unique(['loan_product_id', 'branch_id', 'role_name', 'sequence'], 'approval_rule_scope_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_rules');
    }
};
