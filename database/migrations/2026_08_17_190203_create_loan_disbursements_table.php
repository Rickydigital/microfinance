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
        Schema::create('loan_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->string('disbursement_number')->unique();
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('deducted_fees', 18, 2)->default(0);
            $table->decimal('net_amount', 18, 2);
            $table->string('method');
            $table->string('destination_reference')->nullable();
            $table->string('provider_reference')->nullable()->index();
            $table->string('status')->default('Pending Authorization');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('disbursed_at')->nullable();
            $table->timestamps();
            $table->unique('loan_application_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_disbursements');
    }
};
