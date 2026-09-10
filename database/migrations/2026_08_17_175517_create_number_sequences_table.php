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
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('key');
            $table->string('prefix');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->unsignedSmallInteger('padding')->default(6);
            $table->boolean('reset_annually')->default(false);
            $table->unsignedSmallInteger('current_year')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
