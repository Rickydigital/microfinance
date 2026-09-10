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
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date')->index();
            $table->decimal('principal_due', 18, 2);
            $table->decimal('interest_due', 18, 2);
            $table->decimal('fees_due', 18, 2)->default(0);
            $table->decimal('penalties_due', 18, 2)->default(0);
            $table->decimal('principal_paid', 18, 2)->default(0);
            $table->decimal('interest_paid', 18, 2)->default(0);
            $table->decimal('fees_paid', 18, 2)->default(0);
            $table->decimal('penalties_paid', 18, 2)->default(0);
            $table->string('status')->default('Pending')->index();
            $table->timestamp('reminder_last_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_installments');
    }
};
