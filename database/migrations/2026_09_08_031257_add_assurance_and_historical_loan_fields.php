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
        Schema::table('loan_product_versions', function (Blueprint $table): void {
            $table->decimal('assurance_percent', 7, 4)->default(3);
        });
        Schema::table('loans', function (Blueprint $table): void {
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->decimal('historical_net_disbursement', 18, 2)->default(0);
            $table->decimal('historical_upfront_income', 18, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('imported_by');
            $table->dropColumn(['imported_at', 'historical_net_disbursement', 'historical_upfront_income']);
        });
        Schema::table('loan_product_versions', fn (Blueprint $table) => $table->dropColumn('assurance_percent'));
    }
};
