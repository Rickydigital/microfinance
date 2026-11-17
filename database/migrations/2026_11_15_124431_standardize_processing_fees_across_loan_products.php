<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('loan_product_versions')->orderBy('id')->each(function (object $version): void {
            $fees = DB::table('product_fees')->where('loan_product_version_id', $version->id)
                ->whereRaw('LOWER(name) = ?', ['processing fee'])->orderBy('id')->get();
            $attributes = ['name' => 'Processing fee', 'calculation_method' => 'Fixed', 'value' => 15000,
                'collection_point' => 'Installment', 'is_financed' => true, 'updated_at' => now()];

            if ($fees->isEmpty()) {
                DB::table('product_fees')->insert($attributes + ['loan_product_version_id' => $version->id, 'created_at' => now()]);

                return;
            }

            DB::table('product_fees')->where('id', $fees->first()->id)->update($attributes);
            DB::table('product_fees')->whereIn('id', $fees->skip(1)->pluck('id'))->delete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing fee configurations cannot be restored safely after normalization.
    }
};
