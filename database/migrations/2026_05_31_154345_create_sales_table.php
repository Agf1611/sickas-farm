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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number')->unique();
            $table->foreignId('buyer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fattening_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('sale_date');
            $table->enum('sale_type', ['per_head', 'bulk', 'per_kg']);
            $table->unsignedInteger('head_count')->default(0);
            $table->decimal('total_weight_kg', 10, 2)->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
