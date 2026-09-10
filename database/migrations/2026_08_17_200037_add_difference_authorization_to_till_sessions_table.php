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
        Schema::table('till_sessions', function (Blueprint $table) {
            $table->foreignId('difference_authorized_by')->nullable()->after('difference_reason')->constrained('users')->restrictOnDelete();
            $table->timestamp('difference_authorized_at')->nullable()->after('difference_authorized_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('till_sessions', function (Blueprint $table) {
            $table->dropForeign(['difference_authorized_by']);
            $table->dropColumn(['difference_authorized_by', 'difference_authorized_at']);
        });
    }
};
