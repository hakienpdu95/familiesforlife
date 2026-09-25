<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            if (! Schema::hasColumn('provinces', 'theme_color')) {
                $table->string('theme_color', 7)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('provinces', 'theme_color')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->dropColumn('theme_color');
            });
        }
    }
};
