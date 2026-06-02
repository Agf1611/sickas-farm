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
        Schema::create('sheep', function (Blueprint $table) {
            $table->id();
            $table->string('tag_number')->unique();
            $table->foreignId('fattening_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pen_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('sex', ['male', 'female'])->nullable();
            $table->unsignedInteger('estimated_age_months')->nullable();
            $table->decimal('initial_weight_kg', 10, 2)->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->enum('status', ['active', 'sold', 'dead', 'lost', 'culled', 'sick'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheep');
    }
};
