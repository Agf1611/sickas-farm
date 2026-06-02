<?php

use App\Models\LivestockType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheep_purchases', function (Blueprint $table): void {
            if (! Schema::hasColumn('sheep_purchases', 'livestock_type_id')) {
                $table->foreignId('livestock_type_id')
                    ->nullable()
                    ->after('fattening_batch_id')
                    ->constrained('livestock_types')
                    ->nullOnDelete();
            }
        });

        Schema::table('sheep', function (Blueprint $table): void {
            if (! Schema::hasColumn('sheep', 'sheep_purchase_id')) {
                $table->foreignId('sheep_purchase_id')
                    ->nullable()
                    ->after('livestock_type_id')
                    ->constrained('sheep_purchases')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('sheep', 'is_estimated')) {
                $table->boolean('is_estimated')
                    ->default(false)
                    ->after('purchase_price');
            }
        });

        Schema::table('fattening_batches', function (Blueprint $table): void {
            if (! Schema::hasColumn('fattening_batches', 'detail_status')) {
                $table->string('detail_status')
                    ->default('incomplete')
                    ->after('purchase_capital');
            }
        });

        $dombaId = LivestockType::query()->where('code', 'DMB')->value('id');

        if ($dombaId) {
            DB::table('sheep_purchases')
                ->whereNull('livestock_type_id')
                ->update(['livestock_type_id' => $dombaId]);

            DB::table('fattening_batches')
                ->whereNull('detail_status')
                ->orWhere('detail_status', '')
                ->update(['detail_status' => 'incomplete']);
        }
    }

    public function down(): void
    {
        Schema::table('fattening_batches', function (Blueprint $table): void {
            if (Schema::hasColumn('fattening_batches', 'detail_status')) {
                $table->dropColumn('detail_status');
            }
        });

        Schema::table('sheep', function (Blueprint $table): void {
            if (Schema::hasColumn('sheep', 'sheep_purchase_id')) {
                $table->dropForeign(['sheep_purchase_id']);
                $table->dropColumn('sheep_purchase_id');
            }

            if (Schema::hasColumn('sheep', 'is_estimated')) {
                $table->dropColumn('is_estimated');
            }
        });

        Schema::table('sheep_purchases', function (Blueprint $table): void {
            if (Schema::hasColumn('sheep_purchases', 'livestock_type_id')) {
                $table->dropForeign(['livestock_type_id']);
                $table->dropColumn('livestock_type_id');
            }
        });
    }
};
