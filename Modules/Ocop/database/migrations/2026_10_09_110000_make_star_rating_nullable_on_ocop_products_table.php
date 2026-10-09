<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->unsignedTinyInteger('star_rating')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ocop_products', function (Blueprint $table) {
            $table->unsignedTinyInteger('star_rating')->nullable(false)->change();
        });
    }
};
