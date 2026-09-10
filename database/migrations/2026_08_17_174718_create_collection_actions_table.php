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
        Schema::create('collection_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_case_id')->constrained()->restrictOnDelete();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->string('result')->nullable();
            $table->date('promise_to_pay_date')->nullable();
            $table->decimal('promise_to_pay_amount', 18, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_actions');
    }
};
