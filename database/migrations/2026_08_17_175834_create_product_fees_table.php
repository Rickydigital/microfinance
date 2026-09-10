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
        Schema::create('product_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_version_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('calculation_method');
            $table->decimal('value', 18, 6);
            $table->string('collection_point');
            $table->boolean('is_financed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_fees');
    }
};
