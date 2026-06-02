<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pens', function (Blueprint $table): void {
            $table->json('condition_photo_paths')->nullable()->after('description');
        });

        Schema::table('sheep', function (Blueprint $table): void {
            $table->json('photo_paths')->nullable()->after('notes');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->json('receipt_photo_paths')->nullable()->after('notes');
        });

        Schema::table('sheep_purchases', function (Blueprint $table): void {
            $table->json('proof_photo_paths')->nullable()->after('notes');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->json('proof_photo_paths')->nullable()->after('notes');
        });

        Schema::table('sheep_incident_records', function (Blueprint $table): void {
            $table->json('photo_paths')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('sheep_incident_records', function (Blueprint $table): void {
            $table->dropColumn('photo_paths');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropColumn('proof_photo_paths');
        });

        Schema::table('sheep_purchases', function (Blueprint $table): void {
            $table->dropColumn('proof_photo_paths');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropColumn('receipt_photo_paths');
        });

        Schema::table('sheep', function (Blueprint $table): void {
            $table->dropColumn('photo_paths');
        });

        Schema::table('pens', function (Blueprint $table): void {
            $table->dropColumn('condition_photo_paths');
        });
    }
};
