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
        Schema::create('loan_guarantors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guarantor_id')->constrained()->restrictOnDelete();
            $table->decimal('guaranteed_amount', 18, 2);
            $table->string('status')->default('Proposed');
            $table->boolean('consent_received')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['loan_application_id', 'guarantor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_guarantors');
    }
};
