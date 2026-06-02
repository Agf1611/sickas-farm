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
            $table->foreignId('supplier_id')->nullable()->after('pen_id')->constrained()->nullOnDelete();
            $table->decimal('purchase_capital', 15, 2)->default(0)->after('initial_total_weight_kg');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn('purchase_capital');
        });
    }
};
