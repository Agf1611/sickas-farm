<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table): void {
            $table->boolean('is_historical')->default(false)->after('purchase_capital');
            $table->text('historical_notes')->nullable()->after('is_historical');
        });
    }

    public function down(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table): void {
            $table->dropColumn(['is_historical', 'historical_notes']);
        });
    }
};
