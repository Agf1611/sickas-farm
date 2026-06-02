<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weighing_records', function (Blueprint $table): void {
            if (! Schema::hasColumn('weighing_records', 'weight_type')) {
                $table->string('weight_type')
                    ->nullable()
                    ->after('record_type');
            }

            if (! Schema::hasColumn('weighing_records', 'source')) {
                $table->string('source')
                    ->nullable()
                    ->after('weight_type');
            }

            if (! Schema::hasColumn('weighing_records', 'qty')) {
                $table->unsignedInteger('qty')
                    ->nullable()
                    ->after('sheep_id');
            }

            if (! Schema::hasColumn('weighing_records', 'average_weight_kg')) {
                $table->decimal('average_weight_kg', 10, 2)
                    ->nullable()
                    ->after('total_weight_kg');
            }
        });

        Schema::table('sheep', function (Blueprint $table): void {
            if (! Schema::hasColumn('sheep', 'current_weight_kg')) {
                $table->decimal('current_weight_kg', 10, 2)
                    ->nullable()
                    ->after('initial_weight_kg');
            }
        });

        DB::table('weighing_records')
            ->whereNull('weight_type')
            ->update([
                'weight_type' => DB::raw("case when record_type = 'individual' then 'per_ekor' else 'batch' end"),
            ]);

        DB::table('weighing_records')
            ->whereNull('source')
            ->update([
                'source' => DB::raw("case when record_type = 'individual' then 'actual_individual' else 'actual_batch' end"),
            ]);

        DB::table('weighing_records')
            ->whereNull('qty')
            ->whereNotNull('head_count')
            ->update(['qty' => DB::raw('head_count')]);

        DB::table('weighing_records')
            ->whereNull('average_weight_kg')
            ->whereNotNull('total_weight_kg')
            ->where(function ($query): void {
                $query->whereNotNull('qty')->orWhereNotNull('head_count');
            })
            ->update([
                'average_weight_kg' => DB::raw('round(total_weight_kg / nullif(coalesce(qty, head_count), 0), 2)'),
            ]);

        DB::table('sheep')
            ->whereNull('current_weight_kg')
            ->whereNotNull('initial_weight_kg')
            ->update(['current_weight_kg' => DB::raw('initial_weight_kg')]);
    }

    public function down(): void
    {
        Schema::table('sheep', function (Blueprint $table): void {
            if (Schema::hasColumn('sheep', 'current_weight_kg')) {
                $table->dropColumn('current_weight_kg');
            }
        });

        Schema::table('weighing_records', function (Blueprint $table): void {
            foreach (['average_weight_kg', 'qty', 'source', 'weight_type'] as $column) {
                if (Schema::hasColumn('weighing_records', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
