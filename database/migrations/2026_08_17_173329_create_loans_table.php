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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('loan_application_id')->unique()->constrained()->restrictOnDelete();
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 18, 2);
            $table->decimal('outstanding_principal', 18, 2);
            $table->decimal('arrears_amount', 18, 2)->default(0);
            $table->unsignedInteger('days_past_due')->default(0)->index();
            $table->string('status')->index();
            $table->date('disbursed_at')->nullable();
            $table->date('maturity_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
