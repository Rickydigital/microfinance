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
        Schema::create('customer_economic_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('occupation')->nullable();
            $table->string('employer_or_business')->nullable();
            $table->decimal('monthly_income', 18, 2)->default(0);
            $table->decimal('monthly_expenses', 18, 2)->default(0);
            $table->decimal('existing_debt_payment', 18, 2)->default(0);
            $table->decimal('business_turnover', 18, 2)->default(0);
            $table->text('income_source')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_economic_profiles');
    }
};
