<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheep_purchases', function (Blueprint $table): void {
            if (! Schema::hasColumn('sheep_purchases', 'purchase_number')) {
                $table->string('purchase_number')->nullable()->unique()->after('id');
            }
        });

        Schema::create('livestock_market_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('livestock_type_id')->constrained('livestock_types')->cascadeOnDelete();
            $table->date('effective_date');
            $table->enum('price_type', ['per_kg', 'per_head'])->default('per_kg');
            $table->decimal('price_per_kg', 15, 2)->nullable();
            $table->decimal('price_per_head', 15, 2)->nullable();
            $table->string('source')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['livestock_type_id', 'effective_date']);
        });

        Schema::create('sale_proposals', function (Blueprint $table): void {
            $table->id();
            $table->string('proposal_number')->unique();
            $table->foreignId('fattening_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('livestock_type_id')->nullable()->constrained('livestock_types')->nullOnDelete();
            $table->date('proposed_date');
            $table->enum('proposal_type', ['batch', 'selected_livestock'])->default('batch');
            $table->unsignedInteger('head_count')->default(0);
            $table->decimal('estimated_total_weight_kg', 10, 2)->nullable();
            $table->foreignId('livestock_market_price_id')->nullable()->constrained('livestock_market_prices')->nullOnDelete();
            $table->decimal('estimated_unit_price', 15, 2)->nullable();
            $table->decimal('estimated_total_amount', 15, 2)->default(0);
            $table->decimal('estimated_profit_loss', 15, 2)->default(0);
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'converted_to_sale', 'cancelled'])->default('draft');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_proposal_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sale_proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sheep_id')->constrained('sheep')->cascadeOnDelete();
            $table->decimal('latest_weight_kg', 10, 2)->nullable();
            $table->decimal('estimated_price', 15, 2)->default(0);
            $table->decimal('estimated_profit_loss', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sale_proposal_id', 'sheep_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->date('movement_date');
            $table->enum('movement_type', ['purchase', 'sale', 'death', 'lost', 'culled', 'transfer', 'adjustment']);
            $table->foreignId('fattening_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sheep_id')->nullable()->constrained('sheep')->nullOnDelete();
            $table->foreignId('livestock_type_id')->nullable()->constrained('livestock_types')->nullOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->unsignedInteger('quantity_in')->default(0);
            $table->unsignedInteger('quantity_out')->default(0);
            $table->integer('balance_after')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['movement_date', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('sale_proposal_items');
        Schema::dropIfExists('sale_proposals');
        Schema::dropIfExists('livestock_market_prices');

        Schema::table('sheep_purchases', function (Blueprint $table): void {
            if (Schema::hasColumn('sheep_purchases', 'purchase_number')) {
                $table->dropUnique(['purchase_number']);
                $table->dropColumn('purchase_number');
            }
        });
    }
};
