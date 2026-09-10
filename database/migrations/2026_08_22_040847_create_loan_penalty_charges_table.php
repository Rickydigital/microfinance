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
        Schema::create('loan_penalty_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_installment_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_penalty_rule_id')->constrained()->restrictOnDelete();
            $table->date('charge_date');
            $table->decimal('calculation_base', 18, 2);
            $table->decimal('amount', 18, 2);
            $table->string('status')->default('Posted');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['loan_installment_id', 'product_penalty_rule_id', 'charge_date'], 'loan_penalty_charge_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_penalty_charges');
    }
};
