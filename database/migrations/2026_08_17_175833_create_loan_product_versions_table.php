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
        Schema::create('loan_product_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->decimal('minimum_amount', 18, 2);
            $table->decimal('maximum_amount', 18, 2);
            $table->unsignedInteger('minimum_term');
            $table->unsignedInteger('maximum_term');
            $table->string('term_unit');
            $table->string('interest_method');
            $table->decimal('interest_rate', 9, 6);
            $table->string('rate_basis')->default('Annual');
            $table->string('repayment_frequency');
            $table->unsignedInteger('principal_grace_period')->default(0);
            $table->unsignedInteger('interest_grace_period')->default(0);
            $table->unsignedSmallInteger('required_guarantors')->default(0);
            $table->boolean('requires_collateral')->default(false);
            $table->decimal('maximum_installment_ratio', 7, 4)->default(0.50);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->unique(['loan_product_id', 'version']);
            $table->timestamps();
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            $table->foreign('loan_product_version_id')->references('id')->on('loan_product_versions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropForeign(['loan_product_version_id']);
        });

        Schema::dropIfExists('loan_product_versions');
    }
};
