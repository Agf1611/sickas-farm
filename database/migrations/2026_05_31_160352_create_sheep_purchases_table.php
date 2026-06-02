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
        Schema::create('sheep_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchase_date');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fattening_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('purchase_type', ['bulk', 'per_head', 'per_kg'])->default('bulk');
            $table->unsignedInteger('head_count')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->nullable();
            $table->decimal('total_purchase_price', 15, 2);
            $table->decimal('transport_cost', 15, 2)->default(0);
            $table->decimal('other_cost', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheep_purchases');
    }
};
