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
        Schema::table('fattening_batches', function (Blueprint $table) {
            $table->decimal('target_sale_average_weight_kg', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table) {
            $table->dropColumn('target_sale_average_weight_kg');
        });
    }
};
