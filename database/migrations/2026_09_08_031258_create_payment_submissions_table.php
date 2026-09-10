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
        Schema::create('payment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('purpose');
            $table->decimal('amount', 18, 2);
            $table->string('payment_method');
            $table->string('external_reference');
            $table->string('reference_key', 64)->unique();
            $table->string('attachment_path')->nullable();
            $table->dateTime('paid_at');
            $table->string('status')->default('Pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('repayment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_submissions');
    }
};
