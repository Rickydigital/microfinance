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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('till_session_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('transaction_number')->unique();
            $table->string('type');
            $table->string('direction');
            $table->decimal('amount', 18, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('status')->default('Posted');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reversed_transaction_id')->nullable()->constrained('cash_transactions')->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
