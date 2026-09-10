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
        Schema::create('loan_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessed_by')->constrained('users')->restrictOnDelete();
            $table->decimal('verified_monthly_income', 18, 2);
            $table->decimal('verified_monthly_expenses', 18, 2);
            $table->decimal('existing_debt_payment', 18, 2)->default(0);
            $table->decimal('disposable_income', 18, 2);
            $table->decimal('proposed_installment', 18, 2);
            $table->decimal('installment_ratio', 7, 4);
            $table->string('risk_grade', 20);
            $table->string('recommendation');
            $table->decimal('recommended_amount', 18, 2)->nullable();
            $table->text('conditions')->nullable();
            $table->text('field_visit_notes')->nullable();
            $table->text('rationale');
            $table->json('exception_flags')->nullable();
            $table->timestamp('assessed_at');
            $table->timestamps();
            $table->index(['loan_application_id', 'recommendation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_assessments');
    }
};
