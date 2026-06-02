<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_proposals', function (Blueprint $table): void {
            if (! Schema::hasColumn('sale_proposals', 'sale_id')) {
                $table->foreignId('sale_id')
                    ->nullable()
                    ->after('approved_at')
                    ->constrained('sales')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_proposals', function (Blueprint $table): void {
            if (Schema::hasColumn('sale_proposals', 'sale_id')) {
                $table->dropForeign(['sale_id']);
                $table->dropColumn('sale_id');
            }
        });
    }
};
