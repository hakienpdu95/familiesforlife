<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            if (!Schema::hasColumn('post_articles', 'main_locale')) {
                $table->string('main_locale', 10)->default('vi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('post_articles', function (Blueprint $table) {
            $cols = array_filter(['main_locale'], fn($c) => Schema::hasColumn('post_articles', $c));
            if (!empty($cols)) $table->dropColumn(array_values($cols));
        });
    }
};