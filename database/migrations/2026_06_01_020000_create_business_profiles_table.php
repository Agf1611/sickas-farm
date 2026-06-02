<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('app_name');
            $table->string('business_name');
            $table->string('bumdes_name');
            $table->string('unit_name');
            $table->string('logo_path')->nullable();
            $table->text('address')->nullable();
            $table->string('village')->nullable();
            $table->string('district')->nullable();
            $table->string('regency')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('legal_number')->nullable();
            $table->string('director_name')->nullable();
            $table->string('unit_head_name')->nullable();
            $table->text('report_footer')->nullable();
            $table->string('default_currency')->default('IDR');
            $table->string('default_weight_unit')->default('kg');
            $table->string('default_quantity_unit')->default('ekor');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
