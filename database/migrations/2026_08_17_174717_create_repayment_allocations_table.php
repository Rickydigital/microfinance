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
        Schema::create('repayment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repayment_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_installment_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('component');
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->index(['repayment_id', 'component']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayment_allocations');
    }
};
