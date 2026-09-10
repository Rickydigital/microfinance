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
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 18, 2);
            $table->string('status')->default('Posted')->index();
            $table->string('payment_method');
            $table->string('external_reference')->nullable()->index();
            $table->timestamp('paid_at')->index();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_repayment_id')->nullable()->constrained('repayments')->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
