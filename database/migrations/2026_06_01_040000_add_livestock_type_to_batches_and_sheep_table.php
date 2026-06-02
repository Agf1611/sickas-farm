<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table): void {
            $table->foreignId('livestock_type_id')
                ->nullable()
                ->after('batch_code');
        });

        Schema::table('sheep', function (Blueprint $table): void {
            $table->foreignId('livestock_type_id')
                ->nullable()
                ->after('tag_number');
        });

        $dombaId = DB::table('livestock_types')->where('code', 'DMB')->value('id');

        if (! $dombaId) {
            $dombaId = DB::table('livestock_types')->insertGetId([
                'name' => 'Domba',
                'code' => 'DMB',
                'quantity_unit' => 'ekor',
                'weight_unit' => 'kg',
                'uses_weight_monitoring' => true,
                'default_sale_target_weight' => 30,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('fattening_batches')
            ->whereNull('livestock_type_id')
            ->update(['livestock_type_id' => $dombaId]);

        DB::table('sheep')
            ->whereNull('livestock_type_id')
            ->update(['livestock_type_id' => $dombaId]);

        Schema::table('fattening_batches', function (Blueprint $table): void {
            $table->foreign('livestock_type_id')
                ->references('id')
                ->on('livestock_types')
                ->nullOnDelete();
        });

        Schema::table('sheep', function (Blueprint $table): void {
            $table->foreign('livestock_type_id')
                ->references('id')
                ->on('livestock_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sheep', function (Blueprint $table): void {
            $table->dropForeign(['livestock_type_id']);
            $table->dropColumn('livestock_type_id');
        });

        Schema::table('fattening_batches', function (Blueprint $table): void {
            $table->dropForeign(['livestock_type_id']);
            $table->dropColumn('livestock_type_id');
        });
    }
};
