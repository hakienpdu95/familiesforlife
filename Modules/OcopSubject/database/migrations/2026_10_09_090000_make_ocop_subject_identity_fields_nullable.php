<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_subjects', function (Blueprint $table) {
            $table->string('tax_code', 20)->nullable()->change();
            $table->string('organization_type', 20)->nullable()->change();
            $table->string('legal_representative', 150)->nullable()->change();
            $table->string('address', 255)->nullable()->change();
            $table->string('gps_coordinates', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ocop_subjects', function (Blueprint $table) {
            $table->string('tax_code', 20)->nullable(false)->change();
            $table->string('organization_type', 20)->nullable(false)->change();
            $table->string('legal_representative', 150)->nullable(false)->change();
            $table->string('address', 255)->nullable(false)->change();
            $table->string('gps_coordinates', 60)->nullable(false)->change();
        });
    }
};
