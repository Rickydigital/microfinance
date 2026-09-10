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
        Schema::create('till_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('opening_balance', 18, 2);
            $table->decimal('expected_balance', 18, 2)->nullable();
            $table->decimal('actual_balance', 18, 2)->nullable();
            $table->decimal('difference_amount', 18, 2)->nullable();
            $table->text('difference_reason')->nullable();
            $table->string('status')->default('Open')->index();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('till_sessions');
    }
};
