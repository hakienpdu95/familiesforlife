<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Default decimal(8,2) caps at 999,999.99 — VND prices exceed this
            $table->decimal('price', 15, 2)->default('0.00')->change();
            $table->decimal('signup_fee', 15, 2)->default('0.00')->change();
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->decimal('price')->default('0.00')->change();
            $table->decimal('signup_fee')->default('0.00')->change();
        });
    }
};
