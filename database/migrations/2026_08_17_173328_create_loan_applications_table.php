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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('loan_product_version_id')->nullable()->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('application_number')->unique();
            $table->string('source')->default('branch');
            $table->decimal('requested_amount', 18, 2);
            $table->unsignedInteger('requested_term');
            $table->string('repayment_frequency');
            $table->text('purpose');
            $table->decimal('declared_monthly_income', 18, 2)->default(0);
            $table->decimal('declared_monthly_expenses', 18, 2)->default(0);
            $table->decimal('existing_debt_payment', 18, 2)->default(0);
            $table->decimal('approved_amount', 18, 2)->nullable();
            $table->string('status')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
