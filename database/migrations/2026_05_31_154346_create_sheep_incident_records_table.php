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
        Schema::create('sheep_incident_records', function (Blueprint $table) {
            $table->id();
            $table->date('incident_date');
            $table->enum('incident_type', ['dead', 'lost', 'culled', 'sick']);
            $table->foreignId('fattening_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sheep_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('head_count')->default(1);
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheep_incident_records');
    }
};
